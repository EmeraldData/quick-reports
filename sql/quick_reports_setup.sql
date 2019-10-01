BEGIN;
CREATE SCHEMA quick_reports;

CREATE TABLE quick_reports.template_types (
	id serial PRIMARY KEY
	,NAME TEXT NOT NULL
	,display_order INT NOT NULL
	,active boolean NOT NULL DEFAULT true
	);

INSERT INTO quick_reports.template_types (
	NAME
	,display_order
	)
VALUES (
	'Count'
	,10
	);

INSERT INTO quick_reports.template_types (
	NAME
	,display_order
	)
VALUES (
	'List'
	,20
	);

CREATE TABLE quick_reports.template_groups (
	id serial PRIMARY KEY
	,NAME TEXT NOT NULL
	,display_order INT NOT NULL
	,active boolean NOT NULL DEFAULT true
	);

INSERT INTO quick_reports.template_groups (
        NAME
	,display_order
	)
VALUES (
	'Acquisitions'
	,5
	);

INSERT INTO quick_reports.template_groups (
	NAME
	,display_order
	)
VALUES (
	'Bills'
	,10
	);

INSERT INTO quick_reports.template_groups (
	NAME
	,display_order
	)
VALUES (
	'Circulations'
	,20
	);

INSERT INTO quick_reports.template_groups (
	NAME
	,display_order
	)
VALUES (
	'Holds'
	,30
	);

INSERT INTO quick_reports.template_groups (
	NAME
	,display_order
	)
VALUES (
	'Items'
	,40
	);

INSERT INTO quick_reports.template_groups (
	NAME
	,display_order
	)
VALUES (
	'Patrons'
	,50
	);

CREATE TABLE quick_reports.templates (
	id serial PRIMARY KEY
	,NAME TEXT NOT NULL
	,description TEXT NOT NULL
	,active boolean NOT NULL DEFAULT true
	,creator INT NOT NULL
	,create_time TIMESTAMP WITH TIME zone NOT NULL DEFAULT now()
	,type_id INT NOT NULL
	,group_id INT NOT NULL
	,reporter_template_id INT NOT NULL
	,reporter_template_data TEXT
	,data TEXT NOT NULL
	,doc_url TEXT
	);

CREATE TABLE quick_reports.draft_reports (
	id serial PRIMARY KEY
	,OWNER INT NOT NULL
	,template INT NOT NULL
	,NAME TEXT
	,description TEXT
	,params TEXT
	,create_time TIMESTAMP WITH TIME zone NOT NULL DEFAULT now()
	);

INSERT INTO permission.perm_list (id,code,description) VALUES (DEFAULT, 'ADMIN_SIMPLE_REPORTS', 'Necessary for Admin of Quick Reports Add-on');
COMMIT;
