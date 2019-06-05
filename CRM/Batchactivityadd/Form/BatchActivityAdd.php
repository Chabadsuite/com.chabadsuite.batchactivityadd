<?php

use CRM_Batchactivityadd_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Batchactivityadd_Form_BatchActivityAdd extends CRM_Core_Form {
  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];

    for ($i=0; $i < 25; $i++) {
      // Set default current date and time.
      $defaults['activity_date_time_' . $i . '_activity'] = date("Y-m-d H:i:s");

      // Set default activity status.
      if (empty($defaults['status_id_' . $i . '_activity'])) {
        $defaults['status_id_' . $i . '_activity'] = 2;
      }

      // Set Assignee as current user.
      $defaults['assignee_contact_id_' . $i . '_activity'] = CRM_Core_Session::singleton()->getLoggedInContactID();
    }

    return $defaults;
  }

  public function buildQuickForm() {

    Civi::resources()->addStyleFile('com.chabadsuite.batchactivityadd', 'css/Batchactivityadd.css');

    for ($i=0; $i < 25; $i++) {
      // Activity Types.
      $unwanted = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, "AND v.name = 'Print PDF Letter'");
      $activityTypes = array_diff_key(CRM_Core_PseudoConstant::ActivityType(FALSE), $unwanted);
      $this->add('select', 'activity_type_id_' . $i . '_activity', ts('Activity Type'), ['' => '- ' . ts('select activity') . ' -'] + $activityTypes, FALSE);

      // Target Contact.
      $this->addEntityRef('target_contact_id_' . $i . '_activity', ts('Target contact'), ['multiple' => TRUE, 'create' => TRUE], FALSE);

      // Assignee Contact.
      $this->addEntityRef('assignee_contact_id_' . $i . '_activity', ts('Assignee'), ['multiple' => TRUE, 'create' => TRUE, 'api' => ['params' => ['is_deceased' => 0]],], FALSE);

      // Subject
      $this->add('text', 'subject_' . $i . '_activity', ts('Subject'), CRM_Core_DAO::getAttribute('CRM_Activity_DAO_Activity', 'activity_subject') );

      // Add engagement level.
      if (CRM_Campaign_BAO_Campaign::isCampaignEnable() && CRM_Campaign_BAO_Campaign::accessCampaign()) {
        $engagementLevels = CRM_Campaign_PseudoConstant::engagementLevel();
        $this->add('select', 'engagement_level_' . $i . '_activity', t('Engagement Index'), $engagementLevels);
      }

      // Activity Date
      $this->add('datepicker', 'activity_date_time_' . $i . '_activity', ts('Date'), NULL, FALSE, ['minDate' => 0]);

      // Duration.
      $this->add('number', 'duration_' . $i . '_activity', ts('Duration (minutes)'), ['class' => 'four', 'min' => 1], FALSE);

      // Activity Status.
      $activityStatus = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'status_id');
      $this->add('select', 'status_id_' . $i . '_activity', t('Activity Status'), $activityStatus);

      // Details
      $this->add('textarea', 'details_' . $i . '_activity', ts('Details'), "cols=30 rows=2" );

      // Tag
      $tags = civicrm_api3('Tag', 'get', ['sequential' => 1,]);
      if (!empty($tags['values'])) {
        foreach ($tags['values'] as $keyId => $tagset) {
          $used = explode(',', CRM_Utils_Array::value('used_for', $tagset, ''));
          if (($tagset['is_tagset'] == 0) && in_array('civicrm_activity', $used)) {
            $tagOptions[$tagset['id']] = $tagset['name'];
          }
        }
        if (!empty($tagOptions)) {
          $this->add('select', 'tag_' . $i . '_activity', t('Tag'), $tagOptions, FALSE, [
            'placeholder' => ts('- select -'),
            'class' => 'taglist',
            'multiple' => TRUE,
          ]);
        }
      }

    }

    // Add buttons.
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Process the form submission.
   */
  public function postProcess() {
    $formValues = $this->controller->exportValues($this->_name);
    $data = [];

    if (!empty($formValues)) {
      for ($i=0; $i < 25; $i++) {
        foreach ($formValues as $key => $value) {
          $serchData = '_' . $i . '_activity';
          if (strpos($key, $serchData) && !empty($formValues['activity_type_id' . $serchData])) {
            $fieldName = explode($serchData, $key);
            $data[$i][$fieldName[0]] = $value;
          }
        }
      }
    }

    if (!empty($data)) {
      $totalActivities = 0;

      foreach ($data as $dataValue) {
        $result = civicrm_api3('Activity', 'create', [
          'activity_type_id' => !empty($dataValue['activity_type_id']) ? $dataValue['activity_type_id'] : '',
          'target_id' => !empty($dataValue['target_contact_id']) ? explode(',',$dataValue['target_contact_id']) : '',
          'assignee_id' => !empty($dataValue['assignee_contact_id']) ? explode(',',$dataValue['assignee_contact_id']) : '',
          'subject' => !empty($dataValue['subject']) ? $dataValue['subject'] : '',
          'engagement_level' => !empty($dataValue['engagement_level']) ? $dataValue['engagement_level'] : '',
          'activity_date_time' => !empty($dataValue['activity_date_time']) ? $dataValue['activity_date_time'] : '',
          'duration' => !empty($dataValue['duration']) ? $dataValue['duration'] : '',
          'status_id' => !empty($dataValue['status_id']) ? $dataValue['status_id'] : '',
          'details' => !empty($dataValue['details']) ? $dataValue['details'] : '',
        ]);

        // Create tags if available.
        if (!empty($result['id']) && !empty($dataValue['tag'])) {
          foreach ($dataValue['tag'] as $tagValue) {
            civicrm_api3('EntityTag', 'create', [
              'entity_table' => "civicrm_activity",
              'tag_id' => $tagValue,
              'entity_id' => $result['id'],
            ]);
          }
        }

        $totalActivities++;
      }

      $status = ts('Created new activity.',
          array(
            'count' => $totalActivities,
            'plural' => 'Created %count new activities.',
          )
        );

      CRM_Core_Session::setStatus($status, ts('Batch Activities Created'), 'success');
      CRM_Utils_System::redirect('/civicrm/admin?reset=1');
    }
    else {
     $status = ts('0 Activities are created.');
     CRM_Core_Session::setStatus($status, ts('Please fill atleast one activity'), 'error');
    }

    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
