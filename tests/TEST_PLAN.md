# Gapaika Planner Test Plan

## Prerequisites
- Valid login credentials (username/password)
- Test site URL

## 1. Authentication

### 1.1 Login
- [ ] Navigate to site, verify login form appears
- [ ] Login with invalid credentials, verify error message
- [ ] Login with valid credentials, verify redirect to planner
- [ ] Verify CSRF token is set in meta tag after login

### 1.2 Logout
- [ ] Click user menu icon
- [ ] Click logout
- [ ] Verify redirect to login page
- [ ] Verify cannot access API without session

## 2. Date Navigation

### 2.1 Basic Navigation
- [ ] Click left arrow, verify previous day loads
- [ ] Click right arrow, verify next day loads
- [ ] Click "Today" button, verify returns to current date
- [ ] Verify date display shows correct format (e.g., "Dec 3, 2024")
- [ ] Verify day of week displays correctly

### 2.2 Calendar Dropdown
- [ ] Click on date display, verify calendar opens
- [ ] Click a different day, verify that day loads
- [ ] Navigate months with arrows
- [ ] Navigate years with double arrows
- [ ] Type date in input field, click Go, verify navigation
- [ ] Click outside calendar, verify it closes

### 2.3 Hebrew Date Display
- [ ] Verify Hebrew date displays below Gregorian date
- [ ] Verify special days (holidays, etc.) show badges when applicable

## 3. Task Management

### 3.1 Add Task
- [ ] Type task text, select priority A, click Add
- [ ] Verify task appears in Priority A section
- [ ] Add tasks with priorities B, C, D
- [ ] Verify each appears in correct section
- [ ] Verify empty text is rejected

### 3.2 Edit Task
- [ ] Click on task text
- [ ] Verify task details modal opens
- [ ] Edit task text, save
- [ ] Verify text updates in list
- [ ] Edit priority, save
- [ ] Verify task moves to new priority section
- [ ] Add/edit notes field
- [ ] Verify notes are saved

### 3.3 Task Status
- [ ] Change status to "planning", verify visual indicator
- [ ] Change status to "in_progress", verify visual indicator
- [ ] Change status to "waiting", verify visual indicator
- [ ] Change status to "done", verify strikethrough styling
- [ ] Verify status persists after page reload

### 3.4 Delete Task
- [ ] Click delete button on a task
- [ ] Verify task is removed from list
- [ ] Verify task doesn't reappear after reload

### 3.5 Drag and Drop Reorder
- [ ] Drag task within same priority section
- [ ] Verify new order persists after reload
- [ ] (Note: cross-priority drag may not be supported)

### 3.6 Copy/Move Task
- [ ] Open task details modal
- [ ] Use copy to date feature
- [ ] Verify task appears on target date
- [ ] Verify original task remains

### 3.7 Carry Forward Tasks
- [ ] Create uncompleted tasks on a past date
- [ ] Navigate to today
- [ ] Verify prompt to carry forward appears (if implemented)
- [ ] Accept carry forward
- [ ] Verify tasks copied to today

## 4. Journal

### 4.1 Write Journal Entry
- [ ] Type content in journal textarea
- [ ] Press Ctrl+Enter to save
- [ ] Verify "Saved" confirmation
- [ ] Reload page, verify content persists

### 4.2 Journal Attachments
- [ ] Click "Add attachment" or drag file
- [ ] Upload an image file
- [ ] Verify thumbnail appears in attachments list
- [ ] Upload a PDF file
- [ ] Verify file appears with icon
- [ ] Click attachment to view/download
- [ ] Delete an attachment
- [ ] Verify it's removed

### 4.3 Journal Index
- [ ] Click "[Month] Index" button
- [ ] Add an index entry for current date
- [ ] Verify entry appears in list
- [ ] Navigate to different month, verify correct entries show
- [ ] Delete an index entry
- [ ] Verify removal

### 4.4 Search Journals
- [ ] Click "Search" button
- [ ] Enter search term that exists in a journal
- [ ] Verify results show matching entries
- [ ] Click a result, verify navigation to that date
- [ ] Search for non-existent term, verify "no results"

## 5. Recurring Tasks

### 5.1 Create Recurring Task
- [ ] Click "Recurring" button
- [ ] Click "Add New"
- [ ] Create "day of month" task (e.g., 15th)
- [ ] Create "day of week" task (e.g., Monday)
- [ ] Create "interval days" task
- [ ] Create "interval weeks" task
- [ ] Create "interval months" task
- [ ] Verify each appears in recurring tasks list

### 5.2 Recurring Task Generation
- [ ] Navigate to a date matching day_of_month pattern
- [ ] Verify task auto-generates
- [ ] Navigate to a date matching day_of_week pattern
- [ ] Verify task auto-generates
- [ ] Navigate to matching interval date
- [ ] Verify task auto-generates
- [ ] Revisit same date, verify no duplicate created

### 5.3 Manage Recurring Tasks
- [ ] Edit a recurring task
- [ ] Verify changes saved
- [ ] Pause/deactivate a recurring task
- [ ] Verify it stops generating
- [ ] Delete a recurring task
- [ ] Verify removal from list

## 6. Zmanim (Prayer Times)

### 6.1 View Zmanim
- [ ] Click "Zmanim" button
- [ ] Verify modal opens with times for current date
- [ ] Verify times are reasonable for configured location
- [ ] Close modal

## 7. Yartzheits

### 7.1 Manage Yartzheits
- [ ] Click "Yartzheits" button (in user menu or header)
- [ ] Add a new yartzheit with name, Hebrew date, relationship
- [ ] Verify it appears in list
- [ ] Navigate to date matching Hebrew date
- [ ] Verify yartzheit appears/is indicated
- [ ] Delete a yartzheit
- [ ] Verify removal

## 8. Settings

### 8.1 Location Settings
- [ ] Open user menu, click Settings
- [ ] Modify location name
- [ ] Modify latitude/longitude
- [ ] Save settings
- [ ] Verify zmanim reflect new location

### 8.2 Other Settings
- [ ] Verify all settings fields save correctly
- [ ] Reload page, verify settings persist

## 9. User Menu & Profile

### 9.1 User Dropdown
- [ ] Click user icon in header
- [ ] Verify dropdown opens
- [ ] Verify username displays
- [ ] Verify Settings, Export, Logout options present
- [ ] Click outside dropdown, verify it closes

### 9.2 Profile Photo
- [ ] Click "Change Photo" in user menu
- [ ] Upload an image
- [ ] Verify user icon changes to uploaded photo
- [ ] Reload page, verify photo persists

### 9.3 Export All
- [ ] Click "Export All" in user menu
- [ ] Verify JSON file downloads
- [ ] Open file, verify it contains tasks, journal entries, settings

## 10. UI/Layout (Desktop)

### 10.1 Header
- [ ] Verify "Gapaika Planner" on left
- [ ] Verify date block is centered
- [ ] Verify buttons on right don't wrap unexpectedly
- [ ] Verify "[Month] Index" button shows current month

### 10.2 Main Layout
- [ ] Verify two-column layout (tasks left, journal right)
- [ ] Verify priority sections A, B, C, D all visible
- [ ] Verify journal textarea is usable

### 10.3 Modals
- [ ] Verify all modals open centered
- [ ] Verify modals can be closed with X button
- [ ] Verify clicking overlay closes modal

## 11. Error Handling

### 11.1 API Errors
- [ ] Verify invalid CSRF token shows error (not raw exception)
- [ ] Verify network errors handled gracefully
- [ ] Verify server errors don't expose stack traces

## 12. Data Persistence

### 12.1 Reload Tests
- [ ] Add task, reload page, verify task present
- [ ] Save journal, reload page, verify content present
- [ ] Change date, reload, verify same date loads
