<?php

/**
 * DAOs provide an OOP-style facade for reading and writing database records.
 *
 * DAOs are a primary source for metadata in older versions of CiviCRM (<5.74)
 * and are required for some subsystems (such as APIv3).
 *
 * This stub provides compatibility. It is not intended to be modified in a
 * substantive way. Property annotations may be added, but are not required.
 *
 * @property int|string|null $id
 * @property int|string|null $from_site_email_address_id
 * @property int|string|null $to_contact_id
 * @property string|null $location_type
 * @property string|null $subject
 * @property string|null $body
 * @property int|string|null $created_id
 * @property string|null $date_created
 * @property string|null $date_sent
 * @property string|null $error_message
 * @property string|null $extra
 */
class CRM_Postbox_DAO_EmailMessage extends CRM_Postbox_DAO_Base {

}
