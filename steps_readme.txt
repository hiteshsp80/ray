Create courses and roles in Moodle
Step 1: Prepare the Course Data File and an enhanced PHP script that reads data from CSV files 
Courses.csv:
shortname,fullname,category
course1,Course One,1
course2,Course Two,1
……



Step 2: Place the files in the Container
Run: docker cp create_courses.php moodle_container_name:/var/www/html/
docker cp courses.csv moodle_container_name:/var/www/html/


***Add Courses****
docker cp create_courses.php 645c53600f9a:/var/www/html/
docker cp courses.csv 645c53600f9a:/var/www/html/


Step 3: Enter the Moodle container
docker exec -it moodle_container_name bash(docker exec -it 3c0ab52468bd bash
)

Step 4: Run the script
php create_courses.php

Add Bulk User and admin
docker cp add_users.php 645c53600f9a:/var/www/html/
docker cp users.csv 645c53600f9a:/var/www/html/

