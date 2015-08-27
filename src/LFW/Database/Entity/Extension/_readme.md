/**
 * Extensions may be written as trait. They can implement methods as described below.
*/
trait Extension {

    public function injectExtensionDependencies() {
        // Request some objects and store them
        // Examples: Auth, Lang, ...
        // Called right after Entity creation
    }

    public function initExtensionExtension() {
        // Add some predefined fields
        // Examples: dt_created, dt_deleted, ...
        // Called after DI
    }

    public function applyExtensionExtension() {
        // Perform some checks and apply extension
        // Examples: SoftDelete
        // Called before executing query
    }

    public function getExtensionTableSuffix() {
        // Return table suffix
        // Examples: _i18n, _l10n, ...
        // Used on data modification when extension is actually extending table
    }

    public function getExtensionFields() {
        // Return fields in table - without foreign keys
        // Examples: title, description, ...
        // Used on data modification when extension is actually extending table
    }

    public function getExtensionForeignKeys() {
        // Return foreign keys
        // Examples: id and lang_id / id and user_group_id
        // Used on data modification for linking main table record with extended table rows
    }

    public function translations() {
        // Add join on main table
        // Examples: $this->hasMany('news_i18n');
    }

}