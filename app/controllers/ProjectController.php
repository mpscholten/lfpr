<?php

use Base\DeveloperQuery;
use Base\ProjectQuery;

class ProjectController extends ApplicationController {
  private $per_page = 12;

   public function searchAction() {
     $searchTerm = urldecode($this->request->getParam("q"));


     $curr_page = intVal($this->request->getParam("p"));
     $init = $curr_page * $this->per_page;
     $search_result = search_projects($searchTerm, $init, $this->per_page);

     $total = $search_result['total'];


     $pages = ceil($total / $this->per_page);



     $this->render(array(
       "results" => $search_result['results'],
       "q" => $searchTerm,
       "pagination" => array(
         "current_page" => $curr_page,
         "total_pages" => $pages,
         "total_results" => $total),
     ));
   }

  public function newAction() {
    $entity = new Project();
    $this->render(array("entity" => $entity));
  }

  public function deleteAction() {
    $id = mysql_real_escape_string($this->request->getParam("id"));
    $userId = current_user()->id;
    $project = load_project_where("id = '$id' and owner_id = '$userId'");
    if ($project) {
      delete_project($project->id);
      $this->flash->setSuccess("Delete successfull!");
    }
    $this->redirect_to(project_list_path());
  }

  public function editAction() {
    $id = mysql_real_escape_string($this->request->getParam("id"));
    $userId = current_user()->id;
    $project = load_project_where("id = '$id' and owner_id = '$userId'");

    $this->render(array("entity" => $project));
  }

  public function showAction() {
    $id = mysql_real_escape_string($this->request->getParam("id"));
    $project = load_project($id);
    $new_faq = new Faq();
    $new_faq->project_id = $id;
    $faqs = $project->getQuestions();

    if($project && !$project->published) {
      $this->flash->setError("This project has not been published yet!");
      $this->redirect_to(project_list_path());
    } else if(!$project){
      $this->flash->setError("Project not found!");
      $this->redirect_to(project_list_path());
    } else {
      //$issue = random_issue($id);
      $issues = list_issue("num desc", 5, "project_id = " . $id);
      $this->render(array("faqs_list" => $faqs, "faq" => $new_faq, "issues" => $issues, "project" => $project));
    }
  }

  public function unpublishAction() {
    $id = mysql_real_escape_string($this->request->getParam("id"));
    $userId = current_user()->id;
    $project = load_project_where("id = '$id' and owner_id = '$userId'");
    if($project == null) {
      $this->flash->setError("Project not found!");
    } else {
      $project->published = 0;
      save_project($project);
      //Create the first set of stats
      $this->flash->setSuccess("Project was un-published correctly!");
    }
    $this->redirect_to(developer_show_path(current_user()));
  }
  public function publishAction() {
    $id = mysql_real_escape_string($this->request->getParam("id"));
    $userId = current_user()->id;
    $project = load_project_where("id = '$id' and owner_id = '$userId'");
    if($project == null) {
      $this->flash->setError("Project not found!");
    } else {
      $project->published = 1;
      save_project($project);
      //Create the first set of stats
      $project->saveInitStats();
      $project->grabHistoricData();
      $this->flash->setSuccess("Project was published correctly!");
    }
    $this->redirect_to(developer_show_path(current_user()));
  }

  public function createAction() {
    $project = new Project();
    $proj = $this->request->getParam("project");

    $dev = DeveloperQuery::create()->findOneByName($this->request->getParam("owner_name"));

    if($dev == null) { //Create the developer if it's not on our database already
      $dev = new Developer();
      $dev->setName($this->request->getParam("owner_name"));
      $dev->setAvatarUrl($this->request->getParam("owner_avatar"));
      $dev->save();
    } else { //Update the avatar if it changed
      if ( $dev->getAvatarUrl() != $this->request->getParam("owner_avatar")) {
        $dev->setAvatarUrl($this->request->getParam("owner_avatar"));
        $dev->save();
      }
    }

    if(!ProjectQuery::create()->filterByName($proj['name'])->findOneByOwnerId($dev->getId())) {
      $projectUrl = $proj['url'];
      $projectUrl = str_replace("https", "", $projectUrl);
      $projectUrl = str_replace("http", "", $projectUrl);
      $projectUrl = str_replace("://", "", $projectUrl);
      $projectUrl = "http://" . $projectUrl;

      $project->setUrl($projectUrl)->setDeveloper($dev)->setPublished(true);
      if($project->save()) {
        //Create the first set of stats
        $project->saveInitStats();
        $project->grabHistoricData();
        $this->flash->setSuccess("The project was added correctly, thanks!");
        $this->redirect_to(project_show_path($project));
      } else {
        $this->render(array("entity" => $project), "new");
      }
    } else {
      $this->flash->setError("This project has already been submited");
      $this->render(array("entity" => $project), "new");
    }
  }

  public function indexAction() {
    $language = urldecode($this->request->getParam("language"));
    $owner = $this->request->getParam("owner");
    $where = " published = 1 ";
    if($language != "" && $language != "All") {
      $where .= " and language = '" . $language ."'";
    }

    if($owner != "") {
      $dev = load_developer_where("name like '%".$owner."%'");
      $where .= " and owner_id = " . $dev->id;
    }
    
    $sort_param = $this->request->getParam("sort");
    $underscore_pos = strrpos($sort_param, "_");
    if($underscore_pos !== false)
        $sort = substr_replace($sort_param, " ", $underscore_pos, 1);
    
    $curr_page = intVal($this->request->getParam("p"));
    $total = count_projects($where);
    $init = $curr_page * $this->per_page;
    $pages = ceil($total / $this->per_page);

    $entity_list = list_project($sort, $init . "," . $this->per_page, $where);
    $this->render(array(
            "entity_list" => $entity_list,
            "pagination" => array(
                      "current_page" => $curr_page,
                      "total_pages" => $pages,
                      "total_results" => $total),
            "search_crit" => array(
                        "lang" => $language,
                        "owner" => $owner,
                        "sort" => $this->request->getParam("sort"))));
  }

  private function queryGithub($usr, $repo) {
    return GithubAPI::queryProjectData($usr, $repo);
  }

  public function grab_dataAction() {
    $url = $this->request->getParam("url");
    $url_parts = explode("/", $url);

    $max = count($url_parts) - 1;

    $data = $this->queryGithub($url_parts[$max -1], $url_parts[$max]);
    //Makiavelo::info("Data returned: " . print_r($data, true));

    if(isset($data->message) && $data->message) {
      $json_array = $data;
    } else {

      $json_array = array(
            "name" => $data->{'name'},
            "raw" => print_r($data, true),
            "description" => $data->{'description'},
            "owner_name" => $data->{'owner'}->{'login'},
            "avatar_url" => $data->{'owner'}->{'avatar_url'},
            "stars" => $data->{'watchers'},
            "forks" => $data->{'forks'},
            "last_update" => $data->{'updated_at'},
            "language" => $data->{'language'},
            "open_issues" => $data->{'open_issues'},
            "closed_issues" => $data->{'closed_issues'}
            );
    }
    $this->render(array("json" => json_encode($json_array)));

  }

 }


?>
