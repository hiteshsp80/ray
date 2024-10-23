# Special Consideration Moodle Plugin

## Overview

The Special Consideration plugin is a custom module for Moodle that allows students to submit special consideration applications directly within their course pages. This plugin streamlines the process of requesting extensions, grade considerations, or grade disputes, making it easier for both students and teachers to manage these requests.

## Features

- **Student Features:**
  - Submit new special consideration applications
  - View previous applications
  - Edit pending applications
  - Withdraw pending applications
  - Receive notifications about application status changes

- **Teacher/Admin Features:**
  - View all submitted applications for a course
  - Approve, decline, or request more information for applications
  - Add comments or feedback to applications
  - Automatic or manual assignment deadline extension upon approval
  - Receive notifications about new applications
  - Configure course-specific settings for the plugin


## File Structure

```
special_consideration/
├── classes/
│   └── form/
│       ├── application_form.php
│       ├── response_form.php
│       └── course_settings_form.php
├── db/
│   ├── install.xml
│   ├── access.php
│   └── messages.php
├── lang/
│   └── en/
│       └── local_special_consideration.php
├── apply.php
├── course_settings.php
├── edit.php
├── lib.php
├── styles.css
├── settings.php
├── view.php
├── version.php
└── withdraw.php
```

## Installation

1. Download the plugin files and place them in the following directory:
   ```
   /path/to/moodle/local/special_consideration/
   ```

2. Log in to your Moodle site as an admin and go to 'Site administration'.

3. Click on 'Notifications' to complete the installation process.

4. Once installed, go to 'Site administration' > 'Plugins' > 'Local plugins' > 'Special Consideration' to configure the plugin settings.

## Usage

### For Students

1. Navigate to your course page.
2. Click on the "Special Consideration" button in the course navigation.
3. To submit a new application:
   - Click "Create New Application"
   - Fill out the form with the required information
   - Upload any supporting documents
   - Click "Save changes" to submit
4. To view or manage existing applications:
   - Click on "Previous Applications"
   - You can view, edit (if pending), or withdraw applications from this page

### For Teachers/Admins

1. Go to the course page where you want to manage special consideration applications.
2. Click on the "Special Consideration" button in the course navigation.
3. You will see a list of all submitted applications for the course.
4. Click on an application to view its details.
5. You can approve, decline, or request more information for each application.
6. Add comments or feedback as necessary.
7. To configure course-specific settings, click on the settings icon in the plugin interface.


## Configuration

Teachers or course administrators can configure the following settings for each course:

1. Navigate to the course page.
2. Click on the "Special Consideration" button in the course navigation.
3. Click on the settings icon in the plugin interface.
4. Configure the following settings:

 - Who can approve/deny applications for this course
 - Option to show only future assessments
 - Number of allowed N/A files
 - Maximum file size for uploads
 - Allowed file types for uploads


5. Save the settings.

## Customization

- Language strings can be customized in the `lang/en/local_special_consideration.php` file.
- Styling can be adjusted in the `styles.css` file.

## Version Information

- Current version: 2024091012 (v0.4.9)
- Release: Alpha
- Requires Moodle version: 2022112800 or later
