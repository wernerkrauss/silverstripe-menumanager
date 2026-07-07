<?php

namespace Netwerkstatt\Menumanager\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DB;

/**
 * Custom migration support for gorriecoe/silverstripe-menu.
 *
 * The default task can only migrate has_many ownership if the old has_one column is
 * on the base Link table. For MenuSet links, that data is stored on the MenuLink table.
 */
class GorriecoeMenuMigrationTaskExtension extends Extension
{
    public function migrateHasOneForLinkSubclass(
        mixed $linkClass,
        string $ownerClass,
        string $hasOneRelation,
        string $hasManyRelation
    ): void {
        if ($ownerClass !== 'gorriecoe\\Menu\\Models\\MenuSet' || $hasOneRelation !== 'MenuSet') {
            return;
        }

        $db = DB::get_conn();
        $schema = DB::get_schema();

        $oldMenuSetTable = $this->getTableOrObsoleteTable('MenuSet');
        $oldMenuLinkTable = $this->getTableOrObsoleteTable('MenuLink');
        $newMenuSetTable = 'Menu_MenuSet';
        $newLinkItemTable = 'Menu_LinkItem';
        $linkTable = 'LinkField_Link';

        if (!$oldMenuSetTable || !$oldMenuLinkTable) {
            return;
        }

        // 1. Migrate MenuSets to Menu_MenuSet
        if ($schema->hasTable($newMenuSetTable)) {
            $this->migrateTableData($oldMenuSetTable, $newMenuSetTable, [
                'ID',
                'Title',
                'Slug',
                'AllowChildren',
                'Created',
                'LastEdited'
            ]);
        }

        // 2. Migrate MenuLink to Menu_LinkItem
        // Note: Menu_LinkItem has fields: ID, MenuSetID, SortOrder, LinkID, Enabled, Created, LastEdited
        // Important: Created and LastEdited are in the old Link table, so we need to join it.
        if ($schema->hasTable($newLinkItemTable)) {
            $quotedNewLinkItemTable = $db->escapeIdentifier($newLinkItemTable);
            $quotedOldMenuLinkTable = $db->escapeIdentifier($oldMenuLinkTable);
            $oldLinkTable = $this->getTableOrObsoleteTable('Link');

            if ($oldLinkTable) {
                $quotedOldLinkTable = $db->escapeIdentifier($oldLinkTable);
                $sql = "INSERT INTO $quotedNewLinkItemTable (ID, MenuSetID, SortOrder, LinkID, Enabled, 
                   Created, LastEdited)
                     SELECT ML.ID, ML.MenuSetID, ML.Sort, ML.ID, 1, L.Created, L.LastEdited
                     FROM $quotedOldMenuLinkTable AS ML
                     INNER JOIN $quotedOldLinkTable AS L ON ML.ID = L.ID
                     ON DUPLICATE KEY UPDATE
                        MenuSetID = VALUES(MenuSetID),
                        SortOrder = VALUES(SortOrder),
                        LinkID = VALUES(LinkID),
                        Enabled = VALUES(Enabled),
                        Created = VALUES(Created),
                        LastEdited = VALUES(LastEdited)";
                $db->query($sql);
            } else {
                // Fallback if Link table is already gone (should not happen during migration task)
                $sql = "INSERT INTO $quotedNewLinkItemTable (ID, MenuSetID, SortOrder, LinkID, Enabled)
                     SELECT ID, MenuSetID, Sort, ID, 1 FROM $quotedOldMenuLinkTable
                     ON DUPLICATE KEY UPDATE MenuSetID = VALUES(MenuSetID), SortOrder = VALUES(SortOrder), 
                                             LinkID = VALUES(LinkID), Enabled = VALUES(Enabled)";
                $db->query($sql);
            }
        }

        // 3. Update LinkField_Link to point to LinkItem as Owner
        if ($schema->hasTable($linkTable)) {
            $this->updateLinkOwners($linkTable, $newLinkItemTable);

            // Handle Versioned tables for Links
            if ($schema->hasTable($linkTable . '_Live')) {
                $this->updateLinkOwners($linkTable . '_Live', $newLinkItemTable);
            }
            if ($schema->hasTable($linkTable . '_Versions')) {
                $this->updateLinkOwners($linkTable . '_Versions', $newLinkItemTable);
            }
        }

        // 4. Cleanup old tables (like the main task does for 'Link' table)
        $this->cleanupTable($oldMenuLinkTable);
        $this->cleanupTable($oldMenuSetTable);
    }

    private function updateLinkOwners(string $linkTable, string $linkItemTable): void
    {
        $db = DB::get_conn();
        $quotedLinkTable = $db->escapeIdentifier($linkTable);
        $quotedLinkItemTable = $db->escapeIdentifier($linkItemTable);
        $ownerClass = $db->quoteString('Netwerkstatt\\Menumanager\\Model\\LinkItem');
        $ownerRelation = $db->quoteString('Link');

        $sql = "UPDATE $quotedLinkTable
             INNER JOIN $quotedLinkItemTable ON {$quotedLinkTable}.ID = {$quotedLinkItemTable}.LinkID
             SET {$quotedLinkTable}.OwnerID = {$quotedLinkItemTable}.ID,
                 {$quotedLinkTable}.OwnerClass = $ownerClass,
                 {$quotedLinkTable}.OwnerRelation = $ownerRelation";
        $db->query($sql);
    }

    private function migrateTableData(string $oldTable, string $newTable, array $fields): void
    {
        $db = DB::get_conn();
        $quotedOld = $db->escapeIdentifier($oldTable);
        $quotedNew = $db->escapeIdentifier($newTable);
        $escapedFields = array_map([$db, 'escapeIdentifier'], $fields);
        $fieldList = implode(', ', $escapedFields);

        $updates = [];
        foreach ($fields as $field) {
            if ($field === 'ID') {
                continue;
            }
            $escapedField = $db->escapeIdentifier($field);
            $updates[] = sprintf('%s = VALUES(%s)', $escapedField, $escapedField);
        }
        $updateClause = implode(', ', $updates);

        $sql = "INSERT INTO $quotedNew ($fieldList) SELECT $fieldList FROM $quotedOld 
                                       ON DUPLICATE KEY UPDATE $updateClause";
        $db->query($sql);
    }

    private function getTableOrObsoleteTable(string $tableName): ?string
    {
        $allTables = DB::table_list();
        if (array_key_exists(strtolower($tableName), $allTables)) {
            return $tableName;
        }
        $obsolete = '_obsolete_' . $tableName;
        if (array_key_exists(strtolower($obsolete), $allTables)) {
            return $obsolete;
        }
        return null;
    }

    private function cleanupTable(string $tableName): void
    {
        DB::get_conn()->query(sprintf('DROP TABLE IF EXISTS %s', DB::get_conn()->escapeIdentifier($tableName)));
    }
}
