<?php
/**************************************************************************
	* MantisBT Seeder Plugin
	* Copyright (c) MantisHub - Victor Boctor
	* All rights reserved.
	* MIT License
	**************************************************************************/

require_once(dirname(__FILE__) . '/MantisPhpClient.php');

define('MANTIS_SEEDER_ISSUES_COUNT', 10);

/**
	* Seeder
	*/
class Seeder
{
		/**
			* Seed the database with test projects.
			*
			* return array Array of created project ids.
			*/
		function createProjects(): array
		{
				$t_project_ids = array();
				
				$projects = [
						[
								'p_name' => 'MantisBT',
								'p_description' => 'Mantis Bug Tracker',
						],
						[
								'p_name' => 'MantisHub',
								'p_description' => 'MantisBT as a Service',
						]
				];
				
				foreach ($projects as $project){
						$t_project_ids[] = $this->ensure_project_exists($project['p_name'], $project['p_description']);
				}
				
				return $t_project_ids;
		}
		
		/**
			* Seed the database with test issues.
			*
			* @param array $p_project_ids The projects to use for test issues.
			* @param integer $p_issues_count The number of issues to seed.
			*/
		function createIssues($p_project_ids, $p_issues_count = MANTIS_SEEDER_ISSUES_COUNT)
		{
				$t_client = new MantisPhpClient('https://www.mantisbt.org/bugs/', '', '');
				
				$t_issues_count = 0;
				$t_ids_tested = array();
				
				while (true) {
						$t_issue_id = rand(19000, 20500);
						if (!isset($t_ids_tested[$t_issue_id])) {
								$t_ids_tested[$t_issue_id] = true;
						}
						
						try {
								$t_issue = $t_client->getIssue($t_issue_id);
								$t_issues_count++;
						} catch (SoapFault $e) {
								if (strstr($e->getMessage(), 'Issue does not exist')) {
										continue;
								}
						}
						
						$this->ensure_user_exists($t_issue->reporter);
						
						$t_handler_id = 0;
						
						if (isset($t_issue->handler)) {
								$this->ensure_user_exists($t_issue->handler);
								$t_handler_id = user_get_id_by_name($t_issue->handler->name);
						}
						
						$t_bug_data = new BugData;
						$t_bug_data->project_id = $p_project_ids[rand(0, 1)];
						$t_bug_data->reporter_id = user_get_id_by_name($t_issue->reporter->name);
						$t_bug_data->handler_id = $t_handler_id;
						$t_bug_data->view_state = rand(1, 10);
						$t_bug_data->category_id = 1;
						$t_bug_data->reproducibility = $t_issue->reproducibility->id;
						$t_bug_data->severity = $t_issue->severity->id;
						$t_bug_data->priority = $t_issue->priority->id;
						$t_bug_data->projection = $t_issue->projection->id;
						$t_bug_data->eta = $t_issue->eta->id;
						$t_bug_data->resolution = $t_issue->resolution->id;
						$t_bug_data->status = $t_issue->status->id;
						$t_bug_data->summary = "Test";
						$t_bug_data->description = "Test";
						
						$t_bug_id = $t_bug_data->create();
						
						if ($t_issues_count == $p_issues_count) {
								break;
						}
				}
		}
		
		function ensure_project_exists($p_name, $p_description)
		{
				$t_project_id = project_get_id_by_name($p_name);
				if ($t_project_id) {
						return $t_project_id;
				}
				
				return project_create($p_name, $p_description, /* status: stable */ 50);
		}
		
		function ensure_user_exists($p_account_info)
		{
				if ($p_account_info === null) {
						return;
				}
				
				$t_user_id = user_get_id_by_name($p_account_info->name);
				
				if ($t_user_id === false) {
						user_create($p_account_info->name, '', '', DEVELOPER);
						
				}
				
				return $t_user_id;
		}
}
