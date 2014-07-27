<?php

use Propel\Runtime\Exception\PropelException;

class WebSiteMessageController extends ApplicationController {

  public function newAction() {
    $entity = new WebSiteMessage();
    $this->render(array("entity" => $entity));
  }

  public function deleteAction() {
    WebSiteMessageQuery::create()->findOneById($this->request->getParam("id"))->delete();
    $this->flash->success("Delete successfull!");
    $this->redirect_to(web_site_message_list_path());
  }

  public function editAction() {
    $webSiteMessage = WebSiteMessageQuery::create()->findOneById($this->request->getParam("id"));

    $this->render(array("entity" => $webSiteMessage));
  }

  public function showAction() {
    $webSiteMessage = WebSiteMessageQuery::create()->findOneById($this->request->getParam("id"))
    $this->render(array("entity" => $webSiteMessage));
  }

  public function createAction() {
    $entity = new WebSiteMessage();
    $entity->fromArray($this->request->getParam("web_site_message"));
    try {
        $entity->save();
        $this->flash->setSuccess("Message sent! Thanks for getting in touch!");
        $this->redirect_to(home_root_path_path());
    } catch (PropelException $e) {
      $this->flash->setError("Both fields are required, make sure you fill them! And the email must be  valid one.");
      $this->redirect_to(home_root_path_path());
    }
  }

  public function indexAction() {
    $entity_list = list_web_site_message();
    $this->render(array("entity_list" => $entity_list));
  }


 }


?>
