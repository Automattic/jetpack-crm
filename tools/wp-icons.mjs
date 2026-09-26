/**
 * The icons CRM uses, and the icon-font icons each one replaces.
 *
 * Icons come from three places, in this order of preference, like Jetpack:
 *
 * 1. @wordpress/icons (the default export).
 * 2. social-logos, Automattic's set of social network logos (`socialLogos`).
 * 3. tools/icons/*.svg, for the few gaps: CRM's own icons drawn in the
 *    @wordpress/icons style, and third-party brand marks (`customIcons`).
 *
 * Keys are icon names. Values list selectors for the old Semantic UI, Font
 * Awesome 4 and dashicons icons that should draw it instead, relative to
 * `.jpcrm-admin #wpcontent`. An empty list means the icon is only printed from
 * PHP with jpcrm_wp_icon_svg(), or from Sass with the wp-icon() mixin. Brand
 * marks can also set a `color`.
 *
 * After changing this file, run `npm run build:icons` and commit what it writes:
 * includes/jpcrm-wp-icons.php, sass/_wp-icons.scss and sass/_wp-icons-data.scss.
 */
export default {
	'arrow-left': [ '.fa.fa-arrow-left' ],
	archive: [ 'i.archive.icon', '.fa.fa-archive' ],
	'arrow-right': [ '.fa.fa-long-arrow-right' ],
	backup: [ 'i.heartbeat.icon' ],
	bell: [ '.fa.fa-bell' ],
	calendar: [ 'i.calendar.icon', '.fa.fa-calendar', '#wpbody-content .dashicons-calendar-alt' ],
	cart: [
		'i.cart.icon',
		'i.shopping.cart.icon',
		'.fa.fa-shopping-cart',
		'.fa.fa-file-shopping-cart',
		'#wpbody-content .dashicons-cart',
	],
	caution: [ 'i.exclamation.icon', 'i.exclamation.circle.icon' ],
	check: [ 'i.check.icon', 'i.checkmark.icon', '.fa.fa-check', '#wpbody-content .dashicons-yes' ],
	'chevron-down': [
		'i.search.plus.icon',
		'i.dropdown.icon',
		'i.caret.down.icon',
		'i.angle.down.icon',
		'.fa.fa-angle-down',
		'.fa.fa-chevron-circle-down',
	],
	'chevron-left': [
		'.fc-icon.fc-icon-chevron-left',
		'i.chevron.left.icon',
		'i.angle.double.left.icon',
		'.fa.fa-chevron-left',
		'#wpbody-content .dashicons-arrow-left-alt2',
	],
	'chevron-right': [
		'.fc-icon.fc-icon-chevron-right',
		'.fa.fa-chevron-right',
		'.fa.fa-chevron-circle-right',
		'#wpbody-content .dashicons-arrow-right-alt2',
	],
	'chevron-up': [ 'i.search.minus.icon', 'i.caret.up.icon', 'i.angle.up.icon', 'i.angle.double.up.icon' ],
	'close-small': [
		'i.times.icon',
		'i.remove.icon',
		'i.times.circle.icon',
		'i.remove.circle.icon',
		'i.close.icon',
		'i.delete.icon',
		'.fa.fa-times',
		'#wpbody-content .dashicons-remove',
	],
	'cloud-download': [ 'i.cloud.download.icon' ],
	'cloud-upload': [ 'i.cloud.upload.icon' ],
	code: [ 'i.code.icon', 'i.terminal.icon' ],
	cog: [ 'i.cog.icon', 'i.cogs.icon', 'i.settings.icon', 'i.configure.icon', 'i.wrench.icon', '.fa.fa-cog' ],
	'drag-handle': [ 'i.arrows.alternate.icon' ],
	comment: [ '.fa.fa-commenting', '.fa.fa-comments' ],
	'comment-author-avatar': [ 'i.user.icon', 'i.user.circle.icon', 'i.user.md.icon', 'i.child.icon', '.fa.fa-user' ],
	'currency-dollar': [ 'i.money.icon', '.fa.fa-money' ],
	dashboard: [ 'i.dashboard.icon', '.fa.fa-dashboard' ],
	download: [ 'i.download.icon', '.fa.fa-download' ],
	envelope: [
		'i.envelope.icon',
		'i.mail.icon',
		'i.mail.outline.icon',
		'.fa.fa-envelope-o',
		'.fa.fa-envelope',
	],
	error: [ 'i.warning.icon', 'i.warning.sign.icon', 'i.exclamation.triangle.icon' ],
	external: [ 'i.external.icon', '.fa.fa-share-square-o', '.fa.fa-external-link' ],
	'format-list-bullets': [ 'i.list.icon', 'i.list.layout.icon', 'i.tasks.icon' ],
	fullscreen: [ 'i.expand.icon' ],
	globe: [ 'i.wifi.icon' ],
	funnel: [ 'i.chart.pie.icon', 'i.pie.chart.icon' ],
	grid: [ 'i.th.icon' ],
	group: [ 'i.object.group.icon', '.fa.fa-compress' ],
	help: [ 'i.book.icon' ],
	home: [ 'i.home.icon' ],
	image: [ '.fa.fa-file-image-o' ],
	info: [ 'i.info.icon', '.fa.fa-info-circle' ],
	keyboard: [ '.fa.fa-keyboard-o' ],
	lifesaver: [ 'i.life.ring.icon' ],
	link: [ 'i.linkify.icon', 'i.chain.icon' ],
	'link-off': [ 'i.stop.icon' ],
	'map-marker': [ 'i.map.marker.icon' ],
	mobile: [ 'i.mobile.icon' ],
	'not-allowed': [ 'i.ban.icon', 'i.bell.slash.icon', '.fa.fa-ban' ],
	page: [
		'i.file.icon',
		'.fa.fa-file-text',
		'.fa.fa-file-text-o',
		'.fa.fa-file-pdf-o',
		'.fa.fa-sticky-note-o',
		'#wpbody-content .dashicons-media-text',
	],
	pages: [],
	pause: [ 'i.pause.icon' ],
	payment: [ '.fa.fa-credit-card', 'i.credit.card.icon' ],
	pencil: [ 'i.pencil.icon', 'i.edit.icon', '.fa.fa-pencil', '.fa.fa-pencil-square-o' ],
	pending: [ 'i.hourglass.icon', 'i.minus.circle.icon' ],
	people: [
		'i.users.icon',
		'.fa.fa-users',
		'#wpbody-content .dashicons-groups',
		'#wpbody-content .dashicons-admin-users',
	],
	pin: [ '.fa.fa-thumb-tack' ],
	play: [ 'i.play.icon' ],
	plugins: [ 'i.plug.icon', 'i.puzzle.piece.icon', '.fa.fa-plug' ],
	'post-author': [ 'i.address.book.icon', 'i.address.card.icon', 'i.id.card.icon', '.fa.fa-id-card' ],
	plus: [ 'i.plus.icon', 'i.add.icon' ],
	'plus-circle': [
		'i.plus.circle.icon',
		'i.add.circle.icon',
		'i.plus.square.outline.icon',
		'.fa.fa-plus-circle',
	],
	// Font Awesome's WPForms logo stood for "a form" in general: Gravity Forms, Contact Form 7, Jetpack forms.
	'post-comments-form': [ '.fa.fa-wpforms' ],
	published: [ 'i.check.circle.icon', '.fa.fa-check-circle' ],
	receipt: [],
	scheduled: [ 'i.clock.icon' ],
	search: [ 'i.search.icon', '.fa.fa-search' ],
	seen: [ 'i.unhide.icon', 'i.eye.icon' ],
	send: [ 'i.paper.plane.icon', 'i.mail.forward.icon' ],
	shuffle: [ '.fa.fa-random' ],
	'star-empty': [ 'i.star.outline.icon' ],
	'star-filled': [ 'i.star.icon' ],
	store: [ 'i.building.icon', '#wpbody-content .dashicons-store' ],
	table: [ 'i.table.icon' ],
	tag: [ 'i.tag.icon', 'i.tags.icon' ],
	'thumbs-up': [ '.fa.fa-thumbs-o-up' ],
	tip: [ 'i.magic.icon' ],
	tool: [ 'i.server.icon' ],
	trash: [ 'i.trash.icon', '.fa.fa-trash', '.fa.fa-trash-o' ],
	'trending-up': [ 'i.rocket.icon' ],
	ungroup: [ 'i.object.ungroup.icon' ],
	unlock: [ '#wpbody-content .dashicons-unlock' ],
	update: [ 'i.sync.icon', 'i.exchange.icon' ],
	upload: [ 'i.upload.icon', '.fa.fa-upload' ],
	wordpress: [ 'i.wordpress.icon' ],
};

/**
 * social-logos: the social networks' own logos, drawn in the text color like the
 * rest. The contact's Twitter field still links to twitter.com, so it keeps the
 * Twitter bird rather than the X logo.
 */
export const socialLogos = {
	facebook: [ 'i.facebook.icon', '.fa.fa-facebook', '.fa.fa-facebook-official' ],
	linkedin: [ 'i.linkedin.icon', '.fa.fa-linkedin' ],
	twitter: [ 'i.twitter.icon', '.fa.fa-twitter' ],
};

/**
 * tools/icons/*.svg: see tools/icons/README.md for where each one comes from.
 * Brand marks keep the brand's color instead of the text color.
 */
export const customIcons = {
	envato: { color: '#87e64b', replaces: [ '.fa.fa-envira' ] },
	logout: [ 'i.sign.out.icon' ],
	paypal: { color: '#002991', replaces: [ '.fa.fa-paypal' ] },
	phone: [ 'i.phone.icon', 'i.call.icon', '.fa.fa-phone', '.fa.fa-phone-square' ],
	stripe: { color: '#635bff', replaces: [ '.fa.fa-stripe' ] },
};
