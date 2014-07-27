<?php

use Base\IssueQuery;

class IssueController extends ApplicationController {

 	public function newAction() {
		$entity = new Issue();
		$this->render(array("entity" => $entity));
	}

	public function deleteAction() {
		
	}

	public function editAction() {

	}

	public function showAction() {
		$issue = IssueQuery::create()->findOneById($this->request->getParam("id"));
		$this->render(array("entity" => $issue));
	}

	public function createAction() {

	}

	public function indexAction() {
		$page = $this->request->getParam("p");
		$project_id = $this->request->getParam("pid");
		$per_page = 5;
		$init = $page * $per_page;
        $items = IssueQuery::create()
            ->orderByNum(IssueQuery::DESC)
            ->offset($init)
            ->limit($per_page)
            ->filterByProjectId($project_id)
            ->find();
		$total_results = IssueQuery::create()->filterByProjectId($project_id)->count();
		$total_pages = ceil($total_results / $per_page);
		$this->render(array("issues" => $items, 
							"pid" => $project_id,
							"pagination" => array("total_pages" => $total_pages,
												  "total_results" => $total_results,
												  "current_page" => $page)));
	}

 }
?>
