#!/usr/bin/perl

use strict;
use warnings;

our ($collection_table, $data_table, $start_date, $end_date, $year_month, $consortium_ou);

#B1 - amount owed by patrons
b1 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B1', NULL, actor.org_unit.id, SUM(reporter.classic_current_billing_summary.balance_owed) 
FROM	reporter.classic_current_billing_summary
		INNER JOIN actor.org_unit ON reporter.classic_current_billing_summary.usr_home_ou = actor.org_unit.id
WHERE 	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(reporter.classic_current_billing_summary.last_billing_ts) < '$end_date'	
GROUP BY actor.org_unit.id
)
SQL

b1systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B1', NULL, actor.org_unit.parent_ou, SUM(reporter.classic_current_billing_summary.balance_owed) 
FROM	reporter.classic_current_billing_summary
		INNER JOIN actor.org_unit ON reporter.classic_current_billing_summary.usr_home_ou = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(reporter.classic_current_billing_summary.last_billing_ts) < '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

b1consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B1', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='B1' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#B2 - amount billed to patrons
b2 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B2', NULL, actor.usr.home_ou, SUM(money.billing.amount) 
FROM	money.billable_xact
		LEFT OUTER JOIN money.billing ON money.billable_xact.id = money.billing.xact
		INNER JOIN actor.usr ON money.billable_xact.usr = actor.usr.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND (date(money.billing.billing_ts) IS NULL 
		OR date(money.billing.billing_ts) BETWEEN '$start_date' AND '$end_date')
GROUP BY actor.usr.home_ou
)
SQL

b2systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B2', NULL, actor.org_unit.parent_ou, SUM(money.billing.amount) 
FROM	money.billable_xact
		LEFT OUTER JOIN money.billing ON money.billable_xact.id = money.billing.xact
		INNER JOIN actor.usr ON money.billable_xact.usr = actor.usr.id
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND (date(money.billing.billing_ts) IS NULL 
		OR date(money.billing.billing_ts) BETWEEN '$start_date' AND '$end_date')
GROUP BY actor.org_unit.parent_ou
)
SQL

b2consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B2', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='B2' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#B3 - amount collected 
b3 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B3', NULL, ou, SUM(amt) as amt from 
(
SELECT	actor.workstation.owning_lib as ou, SUM(money.desk_payment_view.amount) as amt
FROM	money.desk_payment_view
		INNER JOIN actor.workstation ON money.desk_payment_view.cash_drawer = actor.workstation.id
where 	actor.workstation.owning_lib in (SELECT id from actor.org_unit WHERE ou_type=3)  
		AND date(money.desk_payment_view.payment_ts) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.workstation.owning_lib
UNION ALL
SELECT	actor.org_unit.id as ou, SUM(money.credit_card_payment.amount) as amt
FROM	money.credit_card_payment 
		INNER JOIN money.billable_xact ON money.credit_card_payment.xact = money.billable_xact.id
		INNER JOIN actor.usr ON money.billable_xact.usr = actor.usr.id
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND money.credit_card_payment.cc_processor is not NULL
		AND money.credit_card_payment.cash_drawer is NULL
		AND date(money.credit_card_payment.payment_ts) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id 
) b3 
GROUP BY ou
)
SQL

b3systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B3', NULL, ou, SUM(amt) as amt from 
(
SELECT	actor.org_unit.parent_ou as ou, SUM(money.desk_payment_view.amount) as amt
FROM	money.desk_payment_view
		INNER JOIN actor.workstation ON money.desk_payment_view.cash_drawer = actor.workstation.id
		INNER JOIN actor.org_unit ON actor.workstation.owning_lib = actor.org_unit.id 
WHERE 	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND date(money.desk_payment_view.payment_ts) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
UNION ALL
SELECT	actor.org_unit.parent_ou as ou, SUM(money.credit_card_payment.amount) as amt
FROM	money.credit_card_payment 
		INNER JOIN money.billable_xact ON money.credit_card_payment.xact = money.billable_xact.id
		INNER JOIN actor.usr ON money.billable_xact.usr = actor.usr.id
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND money.credit_card_payment.cc_processor is not NULL
		AND money.credit_card_payment.cash_drawer is NULL
		AND date(money.credit_card_payment.payment_ts) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou 
) b3 
GROUP BY ou
)
SQL

b3consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'B3', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='B3' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#C1 - count of circulations
c1 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C1', NULL, circ_lib, COUNT(action.all_circulation_combined_types.id) 
FROM	action.all_circulation_combined_types 
WHERE	circ_lib in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.all_circulation_combined_types.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY circ_lib
)
SQL

c1systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C1', NULL, actor.org_unit.parent_ou, COUNT(action.all_circulation_combined_types.id) 
FROM	action.all_circulation_combined_types 
		INNER JOIN actor.org_unit ON action.all_circulation_combined_types.circ_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.all_circulation_combined_types.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

c1consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C1', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='C1' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#C2 - count of circulations by circ modifier
c2 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C2', config.circ_modifier.name, actor.org_unit.id, COUNT(action.circulation.id) 
FROM	action.circulation 
		INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
		INNER JOIN asset.copy ON action.circulation.target_copy = asset.copy.id
		INNER JOIN config.circ_modifier ON asset.copy.circ_modifier = config.circ_modifier.code
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.circulation.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id, config.circ_modifier.name
)
SQL

c2systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C2', config.circ_modifier.name, actor.org_unit.parent_ou, COUNT(action.circulation.id) 
FROM	action.circulation 
		INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
		INNER JOIN asset.copy ON action.circulation.target_copy = asset.copy.id
		INNER JOIN config.circ_modifier ON asset.copy.circ_modifier = config.circ_modifier.code
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.circulation.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou, config.circ_modifier.name
)
SQL

c2consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C2', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='C2' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#C3 - count of circulations by MARC type
c3 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT  now(), $year_month, 'C3', metabib.record_attr_flat.value, actor.org_unit.id, COUNT(action.circulation.id)        
FROM    action.circulation 
        INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
        INNER JOIN asset.copy ON action.circulation.target_copy = asset.copy.id
        INNER JOIN asset.call_number ON (asset.copy.call_number = asset.call_number.id)
        INNER JOIN metabib.record_attr_flat ON (asset.call_number.record = metabib.record_attr_flat.id AND metabib.record_attr_flat.attr = 'item_type')
WHERE   actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
        AND date(action.circulation.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id, metabib.record_attr_flat.value
)
SQL



c3systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C3', asset.copy.circ_as_type, actor.org_unit.parent_ou, COUNT(action.circulation.id) 
FROM	action.circulation 
		INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
		INNER JOIN asset.copy ON action.circulation.target_copy = asset.copy.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.circulation.xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou, asset.copy.circ_as_type
)
SQL

c3consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C3', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='C3' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#C4 - count of circulations by non-catalogued type
c4 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C4', config.non_cataloged_type.name, actor.org_unit.id, COUNT(action.non_cataloged_circulation.id) 
FROM 	action.non_cataloged_circulation 
		INNER JOIN config.non_cataloged_type ON action.non_cataloged_circulation.item_type = config.non_cataloged_type.id
		INNER JOIN actor.org_unit ON action.non_cataloged_circulation.circ_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.non_cataloged_circulation.circ_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id, config.non_cataloged_type.name
)
SQL

c4systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C4', config.non_cataloged_type.name, actor.org_unit.parent_ou, COUNT(action.non_cataloged_circulation.id) 
FROM 	action.non_cataloged_circulation 
		INNER JOIN config.non_cataloged_type ON action.non_cataloged_circulation.item_type = config.non_cataloged_type.id
		INNER JOIN actor.org_unit ON action.non_cataloged_circulation.circ_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.non_cataloged_circulation.circ_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou, config.non_cataloged_type.name
)
SQL

c4consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C4', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='C4' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#C5 - count of in-house circulations
c5 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C5', NULL,  ou, SUM(cnt) as cnt from 
(
SELECT	actor.org_unit.id as ou, COUNT(action.in_house_use.id) as cnt
FROM	action.in_house_use
		INNER JOIN actor.org_unit ON action.in_house_use.org_unit = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.in_house_use.use_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
UNION ALL
SELECT actor.org_unit.id as ou, COUNT(action.non_cat_in_house_use.id) as cnt
FROM	action.non_cat_in_house_use 
		INNER JOIN actor.org_unit ON action.non_cat_in_house_use.org_unit = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.non_cat_in_house_use.use_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
) c5
GROUP BY ou
)
SQL

c5systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C5', NULL,  ou, SUM(cnt) as cnt from 
(
SELECT	actor.org_unit.parent_ou as ou, COUNT(action.in_house_use.id) as cnt
FROM	action.in_house_use
		INNER JOIN actor.org_unit ON action.in_house_use.org_unit = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.in_house_use.use_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
UNION ALL
SELECT actor.org_unit.parent_ou as ou, COUNT(action.non_cat_in_house_use.id) as cnt
FROM	action.non_cat_in_house_use 
		INNER JOIN actor.org_unit ON action.non_cat_in_house_use.org_unit = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.non_cat_in_house_use.use_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
) c5
GROUP BY ou
)
SQL

c5consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'C5', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='C5' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H1 - holds sent
h1 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H1', NULL, actor.org_unit.id, COUNT(action.hold_transit_copy.id) 
FROM	action.hold_transit_copy 
		INNER JOIN actor.org_unit ON action.hold_transit_copy.source = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_transit_copy.source_send_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

h1systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H1', NULL, actor.org_unit.parent_ou, COUNT(action.hold_transit_copy.id) 
FROM	action.hold_transit_copy 
		INNER JOIN actor.org_unit ON action.hold_transit_copy.source = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_transit_copy.source_send_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

h1consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H1', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H1' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H2 - holds received
h2 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H2', NULL, actor.org_unit.id, COUNT(action.hold_transit_copy.id) 
FROM	action.hold_transit_copy 
		INNER JOIN actor.org_unit ON action.hold_transit_copy.dest = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_transit_copy.dest_recv_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

h2systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H2', NULL, actor.org_unit.parent_ou, COUNT(action.hold_transit_copy.id) 
FROM	action.hold_transit_copy 
		INNER JOIN actor.org_unit ON action.hold_transit_copy.dest = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_transit_copy.dest_recv_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

h2consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H2', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H2' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H3 - internal holds
h3 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H3', NULL, actor.usr.home_ou, COUNT(action.hold_request.id) 
FROM	action.hold_request 
		INNER JOIN actor.usr ON action.hold_request.requestor = actor.usr.id
		INNER JOIN asset.copy ON action.hold_request.current_copy = asset.copy.id
		INNER JOIN asset.call_number ON asset.copy.call_number = asset.call_number.id
		AND actor.usr.home_ou = asset.call_number.owning_lib
WHERE 	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_request.fulfillment_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.usr.home_ou
)
SQL

h3systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H3', NULL, actor.org_unit.parent_ou, COUNT(action.hold_request.id) 
FROM	action.hold_request 
		INNER JOIN actor.usr ON action.hold_request.requestor = actor.usr.id
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
		INNER JOIN asset.copy ON action.hold_request.current_copy = asset.copy.id
		INNER JOIN asset.call_number ON asset.copy.call_number = asset.call_number.id
		AND actor.usr.home_ou = asset.call_number.owning_lib
WHERE 	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.hold_request.fulfillment_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

h3consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H3', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H3' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H4 - incoming transits
h4 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H4', NULL, actor.org_unit.id, COUNT(action.transit_copy.id) 
FROM	action.transit_copy 
		INNER JOIN actor.org_unit ON action.transit_copy.dest = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.transit_copy.dest_recv_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

h4systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H4', NULL, actor.org_unit.parent_ou, COUNT(action.transit_copy.id) 
FROM	action.transit_copy 
		INNER JOIN actor.org_unit ON action.transit_copy.dest = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.transit_copy.dest_recv_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

h4consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H4', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H4' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H5 - outgoing transits
h5 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H5', NULL, actor.org_unit.id, COUNT(action.transit_copy.id) 
FROM	action.transit_copy 
		INNER JOIN actor.org_unit ON action.transit_copy.source = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.transit_copy.source_send_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

h5systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H5', NULL, actor.org_unit.parent_ou, COUNT(action.transit_copy.id) 
FROM	action.transit_copy 
		INNER JOIN actor.org_unit ON action.transit_copy.source = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(action.transit_copy.source_send_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

h5consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H5', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H5' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H6 - IntraPines sent
h6 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H6', NULL, s.id, COUNT(action.hold_transit_copy.id) 
FROM 	action.hold_transit_copy 
		JOIN actor.org_unit s on (s.id = action.hold_transit_copy.source) 
		JOIN actor.org_unit r on (r.id = action.hold_transit_copy.dest) 
		JOIN actor.org_unit p on (s.parent_ou = p.id) 
WHERE 	s.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND s.parent_ou <> r.parent_ou 
		AND source_send_time BETWEEN '$start_date' AND '$end_date'
GROUP BY s.id
)
SQL

h6systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H6', NULL, s.parent_ou, COUNT(action.hold_transit_copy.id) 
FROM 	action.hold_transit_copy 
		JOIN actor.org_unit s on (s.id = action.hold_transit_copy.source) 
		JOIN actor.org_unit r on (r.id = action.hold_transit_copy.dest) 
		JOIN actor.org_unit p on (s.parent_ou = p.id) 
WHERE 	s.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND s.parent_ou <> r.parent_ou 
		AND source_send_time BETWEEN '$start_date' AND '$end_date'
GROUP BY s.parent_ou
)
SQL

h6consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H6', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H6' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#H7 - IntraPines received
h7 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H7', NULL, r.id, COUNT(action.hold_transit_copy.id) 
FROM 	action.hold_transit_copy 
		JOIN actor.org_unit s on (s.id = action.hold_transit_copy.source) 
		JOIN actor.org_unit r on (r.id = action.hold_transit_copy.dest) 
		JOIN actor.org_unit p on (r.parent_ou = p.id) 
WHERE 	s.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND s.parent_ou <> r.parent_ou
        AND p.id <> 1
		AND source_send_time BETWEEN '$start_date' AND '$end_date'
GROUP BY r.id
)
SQL

h7systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H7', NULL, r.parent_ou, COUNT(action.hold_transit_copy.id) 
FROM 	action.hold_transit_copy 
		JOIN actor.org_unit s on (s.id = action.hold_transit_copy.source) 
		JOIN actor.org_unit r on (r.id = action.hold_transit_copy.dest) 
		JOIN actor.org_unit p on (r.parent_ou = p.id) 
WHERE 	s.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND s.parent_ou <> r.parent_ou 
		AND source_send_time BETWEEN '$start_date' AND '$end_date'
GROUP BY r.parent_ou
)
SQL

h7consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'H7', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='H7' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL
 
#I1 - total items
i1 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I1', NULL, actor.org_unit.id, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f' 
		AND reporter.classic_item_list.create_date < '$end_date'
GROUP BY actor.org_unit.id
)
SQL

i1systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I1', NULL, actor.org_unit.parent_ou, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f'
		AND reporter.classic_item_list.create_date < '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

i1consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I1', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='I1' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#I2 - value items
i2 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I2', NULL, actor.org_unit.id, SUM(reporter.classic_item_list.price)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f'
		AND reporter.classic_item_list.create_date < '$end_date'
GROUP BY actor.org_unit.id
)
SQL

i2systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I2', NULL, actor.org_unit.parent_ou, SUM(reporter.classic_item_list.price)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f'
		AND reporter.classic_item_list.create_date < '$end_date' 
GROUP BY actor.org_unit.parent_ou
)
SQL

i2consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I2', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='I2' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#I3 - added items
i3 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I3', NULL, actor.org_unit.id, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f' 
		AND date(reporter.classic_item_list.create_date) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

i3systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I3', NULL, actor.org_unit.parent_ou, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 'f' 
		AND date(reporter.classic_item_list.create_date) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

i3consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I3', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='I3' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#I4 - deleted items
i4 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I4', NULL, actor.org_unit.id, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 't' 
		AND date(reporter.classic_item_list.edit_date) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id
)
SQL

i4systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I4', NULL, actor.org_unit.parent_ou, COUNT(reporter.classic_item_list.id)
FROM	reporter.classic_item_list
		INNER JOIN actor.org_unit ON reporter.classic_item_list.owning_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND reporter.classic_item_list.deleted = 't' 
		AND date(reporter.classic_item_list.edit_date) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou
)
SQL

i4consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'I4', NULL, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='I4' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
)
SQL

#P1 - active users
p1 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P1', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.usr.home_ou, COUNT(actor.usr.id) 
FROM	actor.usr 
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND active = 't'
		AND create_date < '$end_date'
GROUP BY actor.usr.home_ou, juvenile
)
SQL

p1systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P1', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.org_unit.parent_ou, COUNT(actor.usr.id) 
FROM	actor.usr 
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND active = 't'
		AND create_date < '$end_date'
GROUP BY actor.org_unit.parent_ou, juvenile
)
SQL

p1consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P1', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='P1' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#P2 - new users
p2 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P2', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.usr.home_ou, COUNT(actor.usr.id) 
FROM	actor.usr
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND create_date BETWEEN '$start_date' AND '$end_date'
		AND active = 't'
GROUP BY actor.usr.home_ou, juvenile
)
SQL

p2systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P2', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.org_unit.parent_ou, COUNT(actor.usr.id) 
FROM	actor.usr
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND create_date BETWEEN '$start_date' AND '$end_date'
		AND active = 't'
GROUP BY actor.org_unit.parent_ou, juvenile
)
SQL

p2consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P2', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='P2' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#P3 - users who circulated items
p3 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P3', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.org_unit.id, COUNT(DISTINCT actor.usr.id) 
FROM	action.circulation
		INNER JOIN actor.usr on action.circulation.usr = actor.usr.id
		INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.id, juvenile
)
SQL

p3systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P3', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.org_unit.parent_ou, COUNT(DISTINCT actor.usr.id) 
FROM	action.circulation
		INNER JOIN actor.usr on action.circulation.usr = actor.usr.id
		INNER JOIN actor.org_unit ON action.circulation.circ_lib = actor.org_unit.id
WHERE	actor.org_unit.id in (SELECT id from actor.org_unit WHERE ou_type=3)
		AND date(xact_start) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou, juvenile
)
SQL

#Note - needed to create unique user+branch combination since a user could have circulations in multiple branches
p3consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P3', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='P3' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

#P4 - users who placed holds
p4 => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P4', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.usr.home_ou, COUNT(DISTINCT actor.usr.id) 
FROM	action.hold_request 
		INNER JOIN actor.usr on action.hold_request.requestor = actor.usr.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND date(action.hold_request.request_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.usr.home_ou, juvenile
)
SQL

p4systems => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P4', CASE WHEN juvenile='t' then 'Juvenile' ELSE 'Adult' END, actor.org_unit.parent_ou, COUNT(DISTINCT actor.usr.id) 
FROM	action.hold_request 
		INNER JOIN actor.usr on action.hold_request.requestor = actor.usr.id
		INNER JOIN actor.org_unit ON actor.usr.home_ou = actor.org_unit.id
WHERE	actor.usr.home_ou in (SELECT id from actor.org_unit WHERE ou_type=3) 
		AND date(action.hold_request.request_time) BETWEEN '$start_date' AND '$end_date'
GROUP BY actor.org_unit.parent_ou, juvenile
)
SQL

p4consortium => <<"SQL",
INSERT INTO $collection_table
(time_stamp, year_month, report, sub_report, org_unit, data)
(
SELECT	now(), $year_month, 'P4', sub_report, $consortium_ou, SUM(data) 
FROM	$collection_table
WHERE	report='P4' AND year_month=$year_month AND org_unit in (SELECT id from actor.org_unit WHERE ou_type=3)
GROUP BY sub_report
)
SQL

consolidate => <<"SQL",
INSERT INTO $data_table
(create_time, org_unit, year_month, b1,b2,b3,c1,c2,c3,c4,c5,h1,h2,h3,h4,h5,h6,h7,i1,i2,i3,i4,p1,p2,p3,p4)
(
select now(), org_unit, $year_month,
(select data as "B1" from quick_reports.executive_reports_data_collection where report='B1' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "B2" from quick_reports.executive_reports_data_collection where report='B2' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "B3" from quick_reports.executive_reports_data_collection where report='B3' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "C1" from quick_reports.executive_reports_data_collection where report='C1' and year_month=$year_month and org_unit=dc.org_unit),
(select array_to_json(array_agg(row)) as "C2" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='C2' and year_month=$year_month and org_unit=dc.org_unit) row),
(select array_to_json(array_agg(row)) as "C3" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='C3' and year_month=$year_month and org_unit=dc.org_unit) row),
(select array_to_json(array_agg(row)) as "C4" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='C4' and year_month=$year_month and org_unit=dc.org_unit) row),
(select data as "C5" from quick_reports.executive_reports_data_collection where report='C5' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H1" from quick_reports.executive_reports_data_collection where report='H1' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H2" from quick_reports.executive_reports_data_collection where report='H2' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H3" from quick_reports.executive_reports_data_collection where report='H3' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H4" from quick_reports.executive_reports_data_collection where report='H4' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H5" from quick_reports.executive_reports_data_collection where report='H5' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H6" from quick_reports.executive_reports_data_collection where report='H6' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "H7" from quick_reports.executive_reports_data_collection where report='H7' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "I1" from quick_reports.executive_reports_data_collection where report='I1' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "I2" from quick_reports.executive_reports_data_collection where report='I2' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "I3" from quick_reports.executive_reports_data_collection where report='I3' and year_month=$year_month and org_unit=dc.org_unit),
(select data as "I4" from quick_reports.executive_reports_data_collection where report='I4' and year_month=$year_month and org_unit=dc.org_unit),
(select array_to_json(array_agg(row)) as "P1" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='P1' and year_month=$year_month and org_unit=dc.org_unit) row),
(select array_to_json(array_agg(row)) as "P2" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='P2' and year_month=$year_month and org_unit=dc.org_unit) row),
(select array_to_json(array_agg(row)) as "P3" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='P3' and year_month=$year_month and org_unit=dc.org_unit) row),
(select array_to_json(array_agg(row)) as "P4" from (select trim(sub_report) as key, data as value from quick_reports.executive_reports_data_collection where report='P4' and year_month=$year_month and org_unit=dc.org_unit) row)
from quick_reports.executive_reports_data_collection dc GROUP BY org_unit order by org_unit
)
SQL

