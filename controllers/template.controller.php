<?php
include 'views/template.view.php';
include_once 'models/db.class.php';
include 'models/template.class.php';

class templateController {
	
	protected $security;

	public function __construct($params) {
		
		$this->security = new security();
		
		switch ($params['action']) {
			case 'list': 
				$showSelectMsg = (isset($params['showSelectMessage']) && $params['showSelectMessage']==true);
				$this->displayTemplateList($params['adminView'], $showSelectMsg); 
				break;
			case 'link': $this->linkTemplate(); break;
			case 'edit': $this->editTemplate($params['id']); break;
		}		
	}
	
	public function displayTemplateList($adminView, $showSelectMessage=false) {

		$adminView = ($adminView && isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']);
		$activeOnly = !$adminView;
		
		$templateObj = new template();
		$templateList = $templateObj->getAllTemplates($activeOnly);
		
		$templateView = new templateView();
		$templateView->showTemplateList($templateList, $adminView, $showSelectMessage);
	}
	
	public function editTemplate($templateID=NULL) {
		
		if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
			new displayMessageView(QR_ADMIN_PERMISSION_REQUIRED_MESSAGE);
			exit;
		}
	
		if (isset($_POST['tid'])) {
			$doUpdate = true;
			$templateID = $_POST['tid'];
		}
		else {	//use templateID tat was passed in
			$doUpdate = false;	
		}

		
		if (!$this->security->validateInteger($templateID)) {
			$error = new displayMessageView('Invalid template ID.');
			exit;
		}		
	
		$templateObj = new template();
		
		if ($doUpdate) {
			$queryParams = new stdClass();
			$queryParams->id=$templateID;
			$queryParams->name=$_POST['templateName'];
			$queryParams->description=$_POST['description'];
			$queryParams->group_id=$_POST['groupList'];
			$queryParams->active=($_POST['active']==1) ? 'true' : 'false';
			$queryParams->type_id=$_POST['typeList'];
			$queryParams->doc_url=$_POST['docURL'];
			$result = $templateObj->editTemplate($queryParams);
		
			if (isset($result->id)) {
				new displayMessageView('The template was updated.', true);
			}
			else {
				new displayMessageView('There was an error updating the template.');
				exit;
			}		
		}
					
		$template = $templateObj->getTemplateInfoForList($templateID);

		if (NULL == $template) {
			$error = new displayMessageView('Unable to locate template.');
			exit;
		}
		
		$typeListData = $templateObj->getTemplateTypesList();
		$groupListData = $templateObj->getTemplategroupsList();
		
		$templateView = new templateView();
		$templateView->displayTemplateInfoForm($template, $typeListData, $groupListData, 'edit');		
	}
		
	public function linkTemplate() {
		
		if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
			$error = new displayMessageView(QR_ADMIN_PERMISSION_REQUIRED_MESSAGE); 
			exit;
		}

		$templateView = new templateView();
	
		if (isset($_POST['tid'])) {		// create the template	
			
			$templateID = $_POST['tid'];
			if ($this->security->validateInteger($templateID)) {

				$templateObj = new template();
				$template = $templateObj->getReporterTemplate($templateID);
				if (NULL == $template) {
					new displayMessageView('Uable to locate template #'.$templateID);
					$templateView->displayLinkTemplateIDForm();
					exit;
				}

				//check if this template has already been linked
				$templateExists = $templateObj->getTemplateByReporterTemplateID($templateID);
				if (NULL != $templateExists) {
					new displayMessageView('Template #'.$templateID. ' has already been linked.');
					$templateView->displayLinkTemplateIDForm();
					exit;
				}
								
				include 'models/templateDecoder.class.php';
				$decoder = new templateDecoder();
				$template->dataDecoded = json_encode($decoder->decodeTemplateData($template->data));
				if (NULL == $template->dataDecoded) {
					new displayMessageView('JSON format error encoding template data.');
					exit;
				}
				
				if (isset($_POST['templateName'])) {	//step 3 - create the new template
					$queryParams = new stdClass();
					$queryParams->name=$_POST['templateName'];
					$queryParams->description=$_POST['description'];
					$queryParams->doc_url=$_POST['docURL'];
					$queryParams->active=($_POST['active']==1) ? 'true' : 'false';
					$queryParams->creator=$_SESSION['userID'];
					$queryParams->create_time='NOW()';
					$queryParams->type_id=$_POST['typeList'];
					$queryParams->group_id=$_POST['groupList'];
					$queryParams->reporter_template_id=$templateID;
					$queryParams->reporter_template_data=$template->data;
					$queryParams->data=$template->dataDecoded;
					$result = $templateObj->createNewTemplate($queryParams);
					
					if (isset($result->id)) 
						new displayMessageView('The template was successully linked.', true);
					else 
						new displayMessageView('There was an error linking the template.', false);
					
					$templateView->displayLinkTemplateIDForm();
				}
				else {	//step 2 - get the template info and display it in a form			
					if ($template == NULL) {
						new displayMessageView('Uable to locate template #'.$templateID);
						$templateView->displayLinkTemplateIDForm();		
					}
					else {	//display the template form
						$typeListData = $templateObj->getTemplateTypesList();
						$groupListData = $templateObj->getTemplategroupsList();
						$template->doc_url=json_decode($template->dataDecoded)->docURL;
						$templateView->displayTemplateInfoForm($template, $typeListData, $groupListData);
					}		
				}
			}
			else {
				new displayMessageView('Invalid template specified #'.$_POST['tid']);
				$templateView->displayLinkTemplateIDForm();
			}

		}
		else { 	//step 1 - get the template ID to import
			$templateView->displayLinkTemplateIDForm();
		}
			
	}
	
}
?>
