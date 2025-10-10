# Organization Deletion Rules

## Overview

This document outlines the business rules and implementation for organization deletion in the Laravel Organization package.

## Business Rules

### 1. Minimum Organization Requirement
**Rule:** Users must maintain at least one organization.

**Implementation:**
```php
$userOrganizationCount = Organization::where('owner_id', $user->id)->count();

if ($userOrganizationCount <= 1) {
    $this->errorMessage = 'Cannot delete your only organization. You must have at least one organization.';
    return;
}
```

**Rationale:** Every user needs at least one organization context to work within. This prevents users from accidentally deleting all their organizations and losing their workspace.

### 2. Active Organization Protection
**Rule:** Cannot delete the currently active organization.

**Implementation:**
```php
if (property_exists($user, 'organization_id') &&
    $user->organization_id === $this->organization->id) {
    $this->errorMessage = 'Cannot delete your current organization. Please switch to another organization first.';
    return;
}
```

**Rationale:** Deleting the active organization would leave the user in an undefined state. Users must explicitly switch to another organization before deleting their current one.

### 3. Member Removal Requirement
**Rule:** All active members (excluding owner) must be removed before deletion.

**Implementation:**
```php
$activeMembersCount = $this->organization->activeUsers()
    ->where('user_id', '!=', $user->id)
    ->count();

if ($activeMembersCount > 0) {
    $this->errorMessage = 'Cannot delete organization with active members. Remove all members first.';
    return;
}
```

**Rationale:** Protects members from losing access without warning and ensures clean data management.

### 4. Owner-Only Deletion
**Rule:** Only the organization owner can delete it.

**Implementation:**
```php
if (! $this->organization->isOwnedBy($user)) {
    $this->errorMessage = 'Only the organization owner can delete the organization.';
    return;
}
```

**Rationale:** Prevents unauthorized deletion by administrators or other members.

### 5. Name Confirmation Required
**Rule:** Users must type the exact organization name to confirm deletion.

**Implementation:**
```php
if ($this->confirmationName !== $this->organization->name) {
    $this->addError('confirmationName', 'Organization name does not match.');
    $this->errorMessage = 'Organization name does not match.';
    return;
}
```

**Rationale:** Prevents accidental deletions by requiring explicit confirmation.

## Deletion Type

### Permanent Deletion (Force Delete)
Organizations are now **permanently deleted** using `forceDelete()` instead of soft delete.

**Before:**
```php
$this->organization->delete(); // Soft delete
```

**After:**
```php
$this->organization->forceDelete(); // Permanent deletion
```

**Why Changed:**
- Permanent deletion is more appropriate for organization management
- Prevents confusion about "deleted" organizations still existing in the database
- Cleaner data management
- Users understand the finality of the action

## User Interface

### Warning Messages
The UI now includes:

1. **Prominent Warning Header**
   - "Permanently Delete Organization" title
   - Red warning icon
   - Bold warning text: "⚠️ WARNING: This action is PERMANENT and cannot be undone!"

2. **Deletion Requirements Checklist**
   ```
   Before you can delete this organization, please note:
   • You must have at least one organization
   • You cannot delete your currently active organization
   • All members must be removed first
   • This deletion is permanent and cannot be undone
   ```

3. **Name Confirmation Input**
   - Enhanced Tailwind CSS styling with error states
   - Clear instruction: "Type [Organization Name] to confirm"
   - Real-time validation feedback

4. **Error Display**
   - Dismissible error alert box
   - Clear error messages explaining what went wrong
   - Icon indicators for better visibility

## Error Messages

All error messages are user-friendly and actionable:

| Scenario | Error Message |
|----------|--------------|
| Only one organization | "Cannot delete your only organization. You must have at least one organization." |
| Current organization | "Cannot delete your current organization. Please switch to another organization first." |
| Has active members | "Cannot delete organization with active members. Remove all members first." |
| Not owner | "Only the organization owner can delete the organization." |
| Name mismatch | "Organization name does not match." |
| General failure | "Failed to delete organization: [technical error]" |

## Testing

### Test Coverage
The following test cases ensure the business rules are properly implemented:

1. ✅ Prevents deletion when user has only one organization
2. ✅ Allows deletion when user has multiple organizations
3. ✅ Prevents deletion of current organization
4. ✅ Allows deletion of non-current organization
5. ✅ Permanently deletes organization using forceDelete
6. ✅ Prevents deletion when organization has active members
7. ✅ Requires exact organization name confirmation
8. ✅ Only allows owner to delete organization

### Running Tests
```bash
./vendor/bin/pest tests/ManageOrganizationDeletionRulesTest.php
```

## Workflow Example

### Successful Deletion Workflow
1. User has multiple organizations (e.g., "Org A", "Org B", "Org C")
2. User's current organization is "Org A"
3. User wants to delete "Org B"
4. User opens manage modal for "Org B"
5. User clicks "Delete Organization" button
6. Confirmation modal appears with warnings
7. User removes all active members from "Org B" (if any)
8. User types "Org B" in confirmation field
9. User clicks "Delete Forever" button
10. "Org B" is permanently deleted
11. Success message: "Organization 'Org B' has been permanently deleted!"
12. User is redirected/page refreshed

### Failed Deletion Scenarios

**Scenario 1: Trying to delete only organization**
```
1. User has only "Org A"
2. User tries to delete "Org A"
3. Error: "Cannot delete your only organization..."
4. Deletion prevented
```

**Scenario 2: Trying to delete current organization**
```
1. User's current org is "Org A"
2. User tries to delete "Org A"
3. Error: "Cannot delete your current organization..."
4. User must switch to another org first
```

**Scenario 3: Organization has members**
```
1. User tries to delete "Org B"
2. "Org B" has 3 active members
3. Error: "Cannot delete organization with active members..."
4. User must remove all members first
```

## Migration Notes

### For Existing Installations
If upgrading from soft delete to permanent delete:

1. **Data Review:** Review any soft-deleted organizations
2. **Cleanup:** Either restore or permanently delete them
3. **Update:** Deploy new deletion logic
4. **Communicate:** Inform users about the permanent deletion policy

### Database Considerations
- No migration needed (SoftDeletes trait remains on model for compatibility)
- `forceDelete()` bypasses the soft delete mechanism
- Existing `deleted_at` column can remain for historical data if needed

## Security Considerations

1. **Authorization:** Only owners can delete organizations
2. **Confirmation:** Name matching prevents accidents
3. **State Protection:** Current organization cannot be deleted
4. **Member Protection:** Cannot delete with active members
5. **Minimum Guarantee:** At least one organization required

## Future Enhancements

Potential improvements for consideration:

1. **Transfer Ownership:** Allow transferring ownership before deletion
2. **Archive Instead:** Provide archiving as alternative to deletion
3. **Bulk Operations:** Allow bulk member removal before deletion
4. **Deletion Logs:** Keep audit trail of deleted organizations
5. **Grace Period:** Optional delay before permanent deletion
6. **Email Notifications:** Notify members when organization is deleted

## Related Files

### PHP Components
- `/src/Livewire/ManageOrganization.php` - Main deletion logic
- `/src/Models/Organization.php` - Organization model with SoftDeletes

### Blade Views
- `/resources/views/livewire/manage-organization.blade.php` - Delete confirmation UI

### Tests
- `/tests/ManageOrganizationDeletionRulesTest.php` - Deletion rules test suite

## Support

For questions or issues related to organization deletion:
1. Check error messages for actionable guidance
2. Review this documentation
3. Check test suite for expected behavior
4. Open issue on GitHub repository
