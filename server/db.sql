create table tests(user_id integer not null, name varbinary(256) not null, test_id integer primary key auto_increment not null, deleted integer(1), time integer not null);
create index tests_idx1 on tests(user_id);
create index tests_idx2 on tests(deleted, time);
create table test_actions(test_id integer not null, type varbinary(256) not null, selector varbinary(4096), data varbinary(4096), user_data varbinary(32768), action_id integer not null);
create unique index test_actions_idx on test_actions(test_id, action_id);
create table test_groups(user_id integer not null, test_group_id integer primary key auto_increment not null, name varbinary(256), deleted integer(1), time integer not null);
create index test_groups_idx1 on test_groups(user_id);
create index test_groups_idx2 on test_groups(deleted, time);
create table tg_tests(test_group_id integer not null, test_id integer not null, test_name varbinary(256) not null, task_type varbinary(256) not null, tg_test_id integer primary key auto_increment not null);
create index tg_tests_idx on tg_tests(test_group_id);
create table tasks(user_id integer not null, test_id integer not null, test_name varbinary(255) not null, type varbinary(256) not null, debug integer(1), status integer, node_id varbinary(256), result varbinary(256), task_id integer primary key auto_increment not null, time integer not null);
create index tasks_idx1 on tasks(status);
create index tasks_idx2 on tasks(user_id, time);
create index tasks_idx3 on tasks(time);
create table task_actions(task_id integer not null, type varbinary(256) not null, selector varbinary(4096), data varbinary(4096), action_id integer not null, scrn varbinary(256), failed varbinary(256));
create unique index task_actions_idx on task_actions(task_id, action_id);
create table task_types(type_id integer primary key auto_increment not null, name varbinary(256) not null, parent_type_id integer);
create index task_types_idx on task_types(name);
create table settings(user_id integer primary key not null, email varbinary(256), task_fail_email_report integer(1), task_success_email_report integer(1), undeletable integer(1));
create table stats(user_id integer not null, time integer not null, tasks_started integer not null default 0, tasks_finished integer not null default 0, tasks_failed integer not null default 0, actions_executed integer not null default 0);
create unique index stats_idx1 on stats(user_id, time);
create index stats_idx2 on stats(time);
create table paypal_subscription_actions(id integer primary key not null, cnt integer not null);
create table demo_subscriptions(id integer primary key not null auto_increment, time integer not null, actions_cnt integer not null, user_id integer not null);
create index demo_subscriptions_idx on demo_subscriptions(user_id);
create table webmoney_transactions(id integer primary key not null auto_increment, time integer not null, user_id integer, external_id integer, url varbinary(256), subscription varbinary(256), actions_cnt integer not null, payment_data varbinary(256), wmid varbinary(16), purse varbinary(16), purse_id integer, started int(1));
create index webmoney_transactions_idx1 on webmoney_transactions(user_id);
create index webmoney_transactions_idx2 on webmoney_transactions(external_id);  -- not unique, external_id may be null
create table webmoney_subscriptions(id integer primary key not null auto_increment, time integer not null, user_id integer, actions_cnt integer not null, wmid varbinary(16), purse varbinary(16));
create index webmoney_subscriptions_idx on webmoney_subscriptions(user_id);
