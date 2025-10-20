# Admin Capabilities Extension - TODO List

## Database Schema Updates
- [ ] Update staff table role enum to include 'admin'
- [ ] Ensure admin_audit_log table exists with proper structure

## Staff Model Enhancements
- [ ] Add get_all_staff() method for admin listing (receptionists and staff only)
- [ ] Add update_staff() method for editing staff accounts
- [ ] Add delete_staff() method for removing staff accounts
- [ ] Add create_staff() method for admin user creation

## Staff Controller Updates
- [ ] Add admin-only endpoints for user management (list, create, edit, delete)
- [ ] Add role-based access control checks for admin endpoints
- [ ] Ensure all admin actions are logged to audit table

## Dashboard Modifications
- [ ] Add admin-specific UI sections for user management only
- [ ] Fix department warning for admin users (admins don't have departments)
- [ ] Admin dashboard shows user management interface instead of queue management

## Reports System Enhancement
- [ ] Modify reports.php to show all departments for admin users
- [ ] Update Report model with admin-level aggregation methods
- [ ] Add overall queue statistics and department performance reports

## Testing and Validation
- [ ] Test admin user management features (CRUD operations for staff/receptionists)
- [ ] Test admin report generation across all departments
- [ ] Verify audit logging for all admin actions
- [ ] Test role-based access control

## Security Considerations
- [ ] Ensure admin endpoints are protected from non-admin access
- [ ] Validate all admin inputs and prevent SQL injection
- [ ] Implement proper session management for admin actions
