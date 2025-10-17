# TODO: Transform Queue History to Grouped View

## Steps to Complete

- [ ] **Step 1: Modify QueueHistory Model**
  - Add a new method `get_grouped_activities()` in `app/models/QueueHistory.php` to fetch activities grouped by patient_id, including patient info and list of actions with timestamps.

- [ ] **Step 2: Update queue_history.php Display Logic**
  - Modify `public/queue_history.php` to use the new grouped data.
  - Change the table structure: Parent row shows patient info (name, check-in time, department, doctor).
  - Add collapsible details/summary element inside each patient row to list all actions with timestamps and departments.

- [ ] **Step 3: Add CSS Styling**
  - Add styles for the collapsible panels (details/summary) to match the existing theme.
  - Ensure responsive design and proper spacing.

- [ ] **Step 4: Update JavaScript Functionality**
  - Ensure select-all, delete, and requeue functionalities work with the new grouped structure.
  - Add any necessary JS for collapsible behavior if details/summary isn't sufficient.

- [ ] **Step 5: Test and Verify**
  - Test the grouped view with sample data.
  - Verify filters (department, date) still apply correctly.
  - Check that actions like delete selected and clear all work properly.
  - Ensure no show/requeue buttons are accessible in the actions list.

## Notes
- Use HTML `<details>` and `<summary>` for collapsible panels.
- Group by patient_id to avoid duplicates.
- Maintain existing filters and actions.
