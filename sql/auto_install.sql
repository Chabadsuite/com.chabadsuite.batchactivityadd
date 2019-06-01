SELECT @domain_id := min(id) FROM civicrm_domain;
SELECT @urlID := MAX(id) FROM civicrm_navigation where name = 'Contacts';
SELECT @urlWeight := MAX(weight)+1 FROM civicrm_navigation where parent_id = @urlID;

INSERT INTO civicrm_navigation
    ( domain_id, url, label, name, permission, permission_operator, parent_id, is_active, has_separator, weight )
VALUES
    ( @domain_id,'civicrm/batch/activity', 'Batch Activities', 'Batch Activities', 'administer CiviCRM', '', @urlID, '1', NULL, @urlWeight );
