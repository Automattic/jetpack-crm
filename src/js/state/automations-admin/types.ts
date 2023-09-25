export type Trigger = {
	slug: string;
	title: string;
	description?: string;
	category: Category;
};

export type Step = {
	id: string;
	attributes: { [ key: string ]: string | number | boolean };
	attribute_definitions: AttributeDefinition[];
	next_step?: string;
	slug: string;
	title: string;
	description: string;
	type: Type;
	category: Category;
	allowedTriggers: Trigger[];
};

export type Workflow = {
	id: number;
	name: string;
	description: string;
	category: Category;
	triggers: Trigger[];
	initial_step: string;
	active: boolean;
	version: number;
	added: string;
	steps: {
		[ stepId: string ]: Step;
	};
};

export type Type = 'contacts';

export type Category = string;

export type AttributeDefinition = {
	slug: string;
	title: string;
	description: string;
	type: AttributeType;
	data?: { [ key: string ]: string };
};

export type AttributeType =
	| 'select'
	| 'checkbox'
	| 'textarea'
	| 'text'
	| 'date'
	| 'datetime'
	| 'number'
	| 'password';
