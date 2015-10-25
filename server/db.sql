create table tests(user_id integer not null, name varchar(256) not null, test_id integer primary key auto_increment not null, deleted integer(1), time integer not null);
create table test_actions(test_id integer not null, type varchar(256) not null, selector varchar(256), data varchar(256), action_id integer not null);
create unique index test_actions_idx on test_actions(test_id, action_id);
create table tasks(user_id integer not null, test_id integer not null, test_name varchar(255) not null, type varchar(256) not null, debug integer(1), status integer not null, node_id varchar(256), result varchar(256), task_id integer primary key auto_increment not null, time integer not null);
create index tasks_idx on tasks(status);
create table task_actions(task_id integer not null, type varchar(256) not null, selector varchar(256), data varchar(256), action_id integer not null, scrn varchar(256), failed varchar(256));
create unique index task_actions_idx on task_actions(task_id, action_id);
create table task_types(type_id integer primary key auto_increment not null, name varchar(256) not null, parent_type_id integer);
create unique index task_types_idx on task_types(name(32));
create table settings(user_id integer primary key not null, email varchar(256), task_fail_email_report integer(1), task_success_email_report integer(1));
create table stats(user_id integer not null, time integer not null, tasks_finished integer not null default 0, tasks_failed integer not null default 0, task_actions_executed integer not null default 0);
create unique index stats_idx on stats(user_id, time);
create table paypal_subscription_actions(id integer primary key not null, cnt integer not null);
create table demo_subscriptions(id integer primary key not null auto_increment, time integer not null, actions_cnt integer not null, user_id integer not null);
create index demo_subscriptions_idx on demo_subscriptions(user_id);
