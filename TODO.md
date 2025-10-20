# Password Reset Functionality Implementation - TODO List

## Database Schema Updates
- [x] Add email column to staff table

## Staff Model Enhancements
- [ ] Add email property to Staff class
- [ ] Add methods for password reset token generation and validation
- [ ] Add method to find staff by email
- [ ] Add method to update password with token validation

## Staff Controller Updates
- [ ] Add forgot_password action (generate token, send email)
- [ ] Add reset_password action (validate token, update password)
- [ ] Add email sending functionality

## Login Page Modifications
- [ ] Add "Forgot Password?" link to login.php

## New Pages Creation
- [ ] Create public/forgot_password.php (form for username/email input)
- [ ] Create public/reset_password.php (form for new password with token)
- [ ] Create CSS styling for new pages

## Security Considerations
- [ ] Implement secure token generation (random, time-limited)
- [ ] Add token expiry (24 hours)
- [ ] Prevent token reuse
- [ ] Log password reset attempts

## Testing and Validation
- [ ] Test forgot password form submission
- [ ] Test email sending functionality
- [ ] Test reset password with valid token
- [ ] Test reset password with expired/invalid token
- [ ] Test password strength validation on reset
- [ ] Verify audit logging for reset attempts
