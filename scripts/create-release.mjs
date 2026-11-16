#!/usr/bin/env zx

import 'zx/globals'

/**
 * Create the release once the release PR is merged.
 *
 * Config-driven, shared across the plugin family (WP Job Manager, WP Super
 * Cache, Crowdsignal Forms, Crowdsignal/Polldaddy). Per-repo specifics live in
 * `release.config.json`; this script is meant to stay identical everywhere.
 *
 * Runs in GitHub Actions after a `release/*` PR is merged: writes the changelog
 * from the (edited) PR body into the readme, tags, builds the zip, and creates
 * the GitHub release. WordPress.org SVN deployment is handled by the workflow
 * after this script runs.
 *
 * The workflow is unattended and can be re-run (re-merge, manual re-trigger),
 * so every mutating step is idempotent: it checks-then-acts and is safe to
 * repeat. If the tag and GitHub release already exist, it short-circuits so the
 * SVN step (which self-skips an existing tag) can still run.
 *
 * The changelog commit is pushed straight to the release PR's base branch. On
 * a repo whose base branch requires status checks, set `requiredCheckWorkflows`
 * in `release.config.json` to the workflow files that report those checks: the
 * commit is then pushed to a temporary `release-changelog/<version>` branch,
 * the workflows are run against it, and the base branch is fast-forwarded once
 * they pass. Each of those workflows needs a `workflow_dispatch` trigger, and
 * the release job needs `actions: write` to start them.
 *
 * Usage: `node scripts/create-release.mjs <pr-number>`.
 *
 * External dependencies
 */
import fs from 'node:fs';
import process from 'node:process';
import { execSync } from 'node:child_process';

const REMOTE   = 'origin';
const cfg      = JSON.parse( fs.readFileSync( 'release.config.json', 'utf8' ) );
const buildDir = cfg.buildDir || 'build';

const pluginFileContents = fs.readFileSync( cfg.mainFile, 'utf8' );
const pluginVersion      = pluginFileContents.match( /Version: (.*)/ )[ 1 ].trim();
const pluginName         = pluginFileContents.match( /Plugin Name: (.*)/ )[ 1 ].trim();

// Validate the parsed version before any git write or shell interpolation: it
// flows into `git tag`/`git push`/`gh release` via execSync template strings, so
// a malformed or hostile `Version:` header must abort here rather than reach a
// shell. Pattern kept in parity with scripts/replace-next-version-tag.sh.
if ( ! /^\d+(\.\d+)+(-(a|alpha|beta)([-.]?\d+)?)?$/.test( pluginVersion ) ) {
	throw new Error( `Refusing to release: "${ pluginVersion }" is not a valid version string (parsed from the "Version:" header in ${ cfg.mainFile }).` );
}

const prNumber = process.argv[ 2 ];

// Parse (and validate) the release notes before any git write, so a malformed
// changelog fence aborts the run before it tags or ships anything.
const releaseNotes = getReleaseNotes();

// If the release already completed on a prior run, skip the changelog/tag/GitHub
// steps. Don't exit early, though: the SVN deploy step runs regardless and reads
// from BUILD_DIR, which is absent on a fresh rerun runner — so we still rebuild
// below so a rerun can recover a failed WordPress.org deploy. On a rerun the
// changelog is already committed to the checked-out branch, so the rebuilt
// package includes it.
const alreadyReleased = remoteTagExists() && githubReleaseExists();

if ( alreadyReleased ) {
	console.log( chalk.yellow( `Release ${ pluginVersion } is already tagged and published on GitHub — rebuilding for the deploy step.` ) );
} else {
	updateChangelog();
	await commitChangelog();
	tagRelease();
}

buildPluginZip();

if ( ! alreadyReleased ) {
	await createGithubRelease();
}

setWorkflowStepOutput();

if ( ! alreadyReleased ) {
	await success();
}

/**
 * Extract the release notes from the release PR body. Throws an explicit error
 * (rather than a cryptic null dereference) if the `### Release Notes` / `---`
 * fences are missing or broken.
 *
 * @return {string} The release notes.
 */
function getReleaseNotes() {
	// Normalize CRLF to LF: GitHub stores PR bodies edited in the web UI with
	// CRLF line endings, which would break the `\n`-based fence regex below.
	// `|| ''` guards an empty/null PR body so the "missing fences" error below
	// fires instead of a null TypeError.
	const prDescription = ( JSON.parse( execSync( `gh pr view ${ prNumber } -R ${ cfg.repo } --json body` ).toString() ).body || '' ).replace( /\r\n/g, '\n' );
	// Both fences must sit on their own lines, and the capture is GREEDY so it
	// runs to the LAST `---` line — the real closing fence. A non-greedy match
	// would stop at the first interior `---` (e.g. a Markdown horizontal rule in
	// the notes) and silently truncate the changelog. Greedy preserves that
	// content instead of dropping everything after it.
	const match         = prDescription.match( /### Release Notes\s*\n---\n([\S\s]*)\n---(?:\n|$)/ );
	if ( ! match ) {
		throw new Error(
			'Could not parse release notes from the PR body. Expected a "### Release Notes" ' +
			'heading followed by the changelog between two "---" fences. Check that both ' +
			'fences are intact in the release PR description.',
		);
	}
	return match[ 1 ].replace( /^- /gm, '* ' ).trim();
}

/**
 * @return {boolean} Whether the version tag already exists on the remote.
 */
function remoteTagExists() {
	return execSync( `git ls-remote --tags ${ REMOTE } refs/tags/${ pluginVersion }` ).toString().trim() !== '';
}

/**
 * @return {boolean} Whether a GitHub release for the version already exists.
 */
function githubReleaseExists() {
	try {
		execSync( `gh release view ${ pluginVersion } -R ${ cfg.repo }`, { stdio: 'ignore' } );
		return true;
	} catch {
		return false;
	}
}

/**
 * @return {boolean} Whether the readme already contains a `### <version>` entry.
 */
function changelogHasVersion() {
	const readme  = fs.readFileSync( cfg.readme, 'utf8' );
	const escaped = pluginVersion.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	return new RegExp( `^### ${ escaped }\\b`, 'm' ).test( readme );
}

function updateChangelog() {
	// Idempotent: if the readme already has this version's entry (e.g. a prior
	// run committed it), don't prepend a duplicate.
	if ( changelogHasVersion() ) {
		console.log( chalk.yellow( `Changelog already contains ### ${ pluginVersion } — skipping.` ) );
		return;
	}

	// The readme's `== Changelog ==` section is the single source of truth.
	// Entries are `### <version> - <date>` blocks, newest first, trimmed to the
	// most recent few. The section is bounded by the next `== ... ==` heading or
	// end of file, so anything after it (e.g. Upgrade Notice) is preserved.
	const newEntry = `### ${ pluginVersion } - ${ new Date().toISOString().slice( 0, 10 ) }\n${ releaseNotes }`;

	let readme = fs.readFileSync( cfg.readme, 'utf8' );

	const replaced = readme.replace(
		/(== Changelog ==\n+)([\s\S]*?)(\n== |$)/,
		( _full, header, bodyBlock, boundary ) => {
			const body     = bodyBlock.trim();
			const existing = body ? body.split( /\n(?=### )/ ).map( ( entry ) => entry.trim() ) : [];
			const entries  = [ newEntry.trim(), ...existing ].slice( 0, 5 );
			const tail     = boundary === '\n== ' ? '\n\n== ' : '\n';
			return `${ header }${ entries.join( '\n\n' ) }${ tail }`;
		},
	);

	if ( replaced === readme ) {
		throw new Error( `Could not find the == Changelog == section in ${ cfg.readme }.` );
	}

	fs.writeFileSync( cfg.readme, replaced );
	console.log( chalk.bold( 'Adding new release to changelog: ' ) );
	console.log( newEntry );
	console.log( chalk.green( '✓' ), cfg.readme );
}

async function commitChangelog() {
	execSync( `git add ${ cfg.readme }` );
	// Idempotent: only commit if the readme actually changed. `git diff --cached
	// --quiet` exits 0 when nothing is staged.
	try {
		execSync( 'git diff --cached --quiet' );
		console.log( chalk.yellow( 'No changelog changes to commit — skipping.' ) );
		return;
	} catch {
		// Non-zero exit means there are staged changes; fall through and commit.
	}
	execSync( `git commit -m "Update changelog for ${ pluginVersion }"` );
	await pushChangelog();
}

/**
 * Push the changelog commit to the base branch.
 *
 * A branch that requires status checks rejects a direct push of a commit those
 * checks haven't run on (GH013), and a push made with the workflow's token
 * doesn't start `push` or `pull_request` workflows. So when the repo lists
 * `requiredCheckWorkflows`, the commit goes to a temporary branch first, the
 * workflows are started on it by hand (`workflow_dispatch` is the one event the
 * workflow token may trigger), and the base branch is fast-forwarded to the
 * same commit once they pass.
 */
async function pushChangelog() {
	const workflows = cfg.requiredCheckWorkflows || [];
	if ( ! workflows.length ) {
		execSync( `git push ${ REMOTE } HEAD` );
		return;
	}

	const baseBranch  = execSync( 'git rev-parse --abbrev-ref HEAD' ).toString().trim();
	const checkBranch = `release-changelog/${ pluginVersion }`;
	const headSha     = () => execSync( 'git rev-parse HEAD' ).toString().trim();

	// More than one attempt only when the base branch moved while the checks
	// ran: the commit is rebased, so it is a new commit and is checked again.
	for ( let attempt = 1; attempt <= 3; attempt++ ) {
		const sha = headSha();
		execSync( `git push --force ${ REMOTE } HEAD:refs/heads/${ checkBranch }` );
		await runRequiredChecks( workflows, checkBranch, sha );

		let pushError;
		try {
			execSync( `git push ${ REMOTE } HEAD:refs/heads/${ baseBranch }`, { stdio: 'pipe' } );
		} catch ( error ) {
			pushError = error;
		}

		if ( ! pushError ) {
			// The temporary branch has done its job. Leaving it behind is harmless
			// (the next run force-pushes over it), so a failed delete isn't fatal.
			try {
				execSync( `git push ${ REMOTE } --delete ${ checkBranch }`, { stdio: 'ignore' } );
			} catch {
				console.log( chalk.yellow( `Could not delete ${ checkBranch } — delete it by hand.` ) );
			}
			return;
		}

		// --autostash: installing dependencies can leave tracked files modified
		// (a lock file, say), and a rebase refuses to start on a dirty tree.
		execSync( `git pull --rebase --autostash ${ REMOTE } ${ baseBranch }` );
		if ( headSha() === sha ) {
			// Nothing new on the base branch, so the push was refused for another
			// reason. Retrying would only repeat it.
			throw new Error(
				`Could not push the changelog commit to ${ baseBranch } after its checks passed. ` +
				`It is on the ${ checkBranch } branch.\n${ pushError.stderr || pushError.message }`,
			);
		}
		console.log( chalk.yellow( `${ baseBranch } moved while the checks ran — rebased, checking the new commit.` ) );
	}

	throw new Error( `Could not push the changelog commit to ${ baseBranch }: it kept moving. The commit is on the ${ checkBranch } branch.` );
}

/**
 * Start each required-check workflow on a branch and wait for all of them to
 * pass on the given commit. Throws if a run can't be found, fails or times out.
 *
 * @param {string[]} workflows Workflow file names, e.g. `phpunit.yml`.
 * @param {string}   ref       Branch to run them on.
 * @param {string}   sha       Commit the runs must be for.
 */
async function runRequiredChecks( workflows, ref, sha ) {
	for ( const workflow of workflows ) {
		await $`gh workflow run ${ workflow } -R ${ cfg.repo } --ref ${ ref }`;
	}
	for ( const workflow of workflows ) {
		const runId = await findWorkflowRun( workflow, ref, sha );
		console.log( chalk.bold( `Waiting for ${ workflow } (run ${ runId })…` ) );
		// --exit-status makes a failed run a non-zero exit, which zx throws on.
		await $`gh run watch ${ runId } -R ${ cfg.repo } --exit-status --interval 10`.quiet().timeout( '1200s' );
	}
}

/**
 * Find the run a `gh workflow run` call just started. The dispatch call returns
 * before the run exists, so poll for it.
 *
 * @param {string} workflow Workflow file name.
 * @param {string} ref      Branch it was started on.
 * @param {string} sha      Commit it must be for.
 * @return {Promise<number>} The run's ID.
 */
async function findWorkflowRun( workflow, ref, sha ) {
	for ( let tries = 0; tries < 24; tries++ ) {
		const runs = JSON.parse(
			( await $`gh run list -R ${ cfg.repo } --workflow ${ workflow } --branch ${ ref } --event workflow_dispatch --json databaseId,headSha --limit 20`.quiet() ).stdout,
		);
		const run  = runs.find( ( candidate ) => candidate.headSha === sha );
		if ( run ) {
			return run.databaseId;
		}
		await sleep( 5000 );
	}
	throw new Error( `${ workflow } did not start on ${ ref } (${ sha }). Does it have a workflow_dispatch trigger?` );
}

function tagRelease() {
	// Idempotent: skip if the tag is already on the remote.
	if ( remoteTagExists() ) {
		console.log( chalk.yellow( `Tag ${ pluginVersion } already on ${ REMOTE } — skipping tag.` ) );
		return;
	}
	const localTagExists = execSync( `git tag -l ${ pluginVersion }` ).toString().trim() !== '';
	if ( ! localTagExists ) {
		execSync( `git tag -a ${ pluginVersion } -m "Release ${ pluginVersion }"` );
	}
	execSync( `git push ${ REMOTE } ${ pluginVersion }` );
}

function buildPluginZip() {
	execSync( `make build 1> /dev/null` );
}

function setWorkflowStepOutput() {
	// Write straight to the file instead of shelling out `echo`, so the version
	// never reaches a shell (no command-substitution or word-splitting surface).
	fs.appendFileSync( process.env.GITHUB_OUTPUT, `version=${ pluginVersion }\n` );
}

async function createGithubRelease() {
	// Idempotent: skip if a release for this version already exists.
	if ( githubReleaseExists() ) {
		console.log( chalk.yellow( `GitHub release ${ pluginVersion } already exists — skipping.` ) );
		return;
	}
	const pluginZip = `${ buildDir }/${ cfg.slug }.zip`;
	await $`gh release create ${ pluginVersion } -R ${ cfg.repo } --title ${ `Version ${ pluginVersion }` } --notes ${ releaseNotes } ${ pluginZip }`;
}

async function success() {
	console.log( chalk.bold.green( `✓ ${ pluginName } ${ pluginVersion } release created!` ) );
	const comment = `✅ **[${ pluginName } ${ pluginVersion } release](https://github.com/${ cfg.repo }/releases/tag/${ pluginVersion })** created!`;
	await $`gh pr comment ${ prNumber } -R ${ cfg.repo } --edit-last --body ${ comment }`;
}
