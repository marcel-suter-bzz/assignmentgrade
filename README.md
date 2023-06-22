Welcome file
Welcome file

# Assignment Grade

This Moodle plugin is being developed to update the students grade from the results of a GitHub Classroom assignment. In the repository is a workflow that executes a number of test cases and calculates the total. This plugin allows me to export the resulting points to the corresponding Moodle assignment.
The plugin is not limited to use with GitHub Classroom, it should work with any external system.
### Limitations
At this time the plugin only works with individual assignments, not for group assignments.
### Disclaimer
This plugin is being developed for my own classes and is still in testing. I try to make this plugin as safe and error free as possible. I cannot give any guarantees or accept any liability if you use this plugin in your Moodle installation. Before use, I encourage you to study the source code (and give me feedback if you find any flaws) and install it in a test instance. 
## Moodle installation and configuration
### External username
To match the grades to the correct user, the username in the external system (i.e. Classroom, ...) must be set in the Moodle user profile. To add an additional field to the user profile see https://docs.moodle.org/402/en/User_profile_fields.
This (FIXME add screenshot) shows my setup.
### Field for assignment name
The Moodle assignment needs a custom field to save the name of the assignment in the external system. Moodle core does not support custom fields for assignments, therefore this plugin requires https://moodle.org/plugins/local_modcustomfields by Daniel Neis Araujo. Install the modcustomfields plugin first and add a custom field.
This (FIXME add screenshot) shows my setup.
### Installation
Download this plugin as a zip-archive and install it in your Moodle. During installation you will be asked to specify the shortnames of the two custom fields:

 - external username
 - assignment name
### Webservice
Create a new external webservice in your Moodle (https://docs.moodle.org/402/en/Using_web_services).
TODO required permissions for user and webservice
## Usage with GitHub Classroom
This section explains how our school uses the plugin with GitHub Classroom (see also https://classroom.github.com/videos).
### User profile in Moodle
Add the GitHub username to the Moodle profile of your students. 
### Secret and Variable
The workflow requires the URL of the moodle webservice. This value can be saved as a variable `MOODLE_URL` in the repository or the GitHub organization.
To authenticate the request, the workflow also needs the moodle token you generated for the webservice. This will be saved as a secret `MOODLE_TOKEN` in the GitHub organization.
### Create template repository
The template repository contains the starting code for your students, a number of tests and a workflow for the autograding in GitHub Classroom. https://github.com/BZZ-Commons/python-template shows the basic template we use at our school.

The workflow is in `.github/workflows/autograding.yml`. It contains 3 steps:

 1. Checkout the files in the repository `- uses: actions/checkout@v3`
 2. Run the tests with autograding `- uses: education/autograding@v1`
 3. Call the moodle plugin `- name: export-grade` 

#### autograding.yml
```
name: GitHub Classroom Workflow
- name: export-grade
on: [push]

permissions:
  checks: write
  actions: read
  contents: read

jobs:
  grading:
    if: ${{ !contains(github.actor, 'classroom') }}
    name: Autograding
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: education/autograding@v1
        id: autograding
      
        if: always()
        run: |
          grade=${{ steps.autograding.outputs.Points }}
          parts=(${grade//\// })
          points="points=${parts[0]}"
          
          user="user_name=${{ github.actor }}"
          
          repofull=${{ github.repository }}
          parts=(${repofull//\// })
          reponame=${parts[1]}
          assignment="${reponame/"-${{ github.actor }}"/""}"
          assignment="assignment_name=$assignment"

          wsfunction="wsfunction=local_gradeassignments_update_grade"
          wstoken="wstoken=${{ secrets.MOODLE_TOKEN }}"
          
          url="${{ vars.MOODLE_URL}}?${wstoken}&${wsfunction}&$assignment&$user&$points"
          curl $url

```
### Create assignments
Create an assignment in GitHub Classroom. 
Create a Moodle assignment and enter the name of the GitHub Classroom assignment in the custom field.
### Auto grading
After the students accept the assignment they solve the assignment and push their code to GitHub. With every push the GitHub workflow runs the tests and calls the Moodle webservice to update the grade for this student.

Assignment Grade
This Moodle plugin is being developed to update the students grade from the results of a GitHub Classroom assignment. In the repository is a workflow that executes a number of test cases and calculates the total. This plugin allows me to export the resulting points to the corresponding Moodle assignment.
The plugin is not limited to use with GitHub Classroom, it should work with any external system.

Limitations
At this time the plugin only works with individual assignments, not for group assignments.

Disclaimer
This plugin is being developed for my own classes and is still in testing. I try to make this plugin as safe and error free as possible. I cannot give any guarantees or accept any liability if you use this plugin in your Moodle installation. Before use, I encourage you to study the source code (and give me feedback if you find any flaws) and install it in a test instance.

Moodle installation and configuration
External username
To match the grades to the correct user, the username in the external system (i.e. Classroom, …) must be set in the Moodle user profile. To add an additional field to the user profile see https://docs.moodle.org/402/en/User_profile_fields.
This (FIXME add screenshot) shows my setup.

Field for assignment name
The Moodle assignment needs a custom field to save the name of the assignment in the external system. Moodle core does not support custom fields for assignments, therefore this plugin requires https://moodle.org/plugins/local_modcustomfields by Daniel Neis Araujo. Install the modcustomfields plugin first and add a custom field.
This (FIXME add screenshot) shows my setup.

Installation
Download this plugin as a zip-archive and install it in your Moodle. During installation you will be asked to specify the shortnames of the two custom fields:

external username
assignment name
Webservice
Create a new external webservice in your Moodle (https://docs.moodle.org/402/en/Using_web_services).
TODO required permissions for user and webservice

Usage with GitHub Classroom
This section explains how our school uses the plugin with GitHub Classroom (see also https://classroom.github.com/videos).

User profile in Moodle
Add the GitHub username to the Moodle profile of your students.

Secret and Variable
The workflow requires the URL of the moodle webservice. This value can be saved as a variable MOODLE_URL in the repository or the GitHub organization.
To authenticate the request, the workflow also needs the moodle token you generated for the webservice. This will be saved as a secret MOODLE_TOKEN in the GitHub organization.

Create template repository
The template repository contains the starting code for your students, a number of tests and a workflow for the autograding in GitHub Classroom. https://github.com/BZZ-Commons/python-template shows the basic template we use at our school.

The workflow is in .github/workflows/autograding.yml. It contains 3 steps:

Checkout the files in the repository - uses: actions/checkout@v3
Run the tests with autograding - uses: education/autograding@v1
Call the moodle plugin - name: export-grade
autograding.yml
name: GitHub Classroom Workflow
- name: export-grade
on: [push]

permissions:
  checks: write
  actions: read
  contents: read

jobs:
  grading:
    if: ${{ !contains(github.actor, 'classroom') }}
    name: Autograding
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: education/autograding@v1
        id: autograding
      
        if: always()
        run: |
          grade=${{ steps.autograding.outputs.Points }}
          parts=(${grade//\// })
          points="points=${parts[0]}"
          
          user="user_name=${{ github.actor }}"
          
          repofull=${{ github.repository }}
          parts=(${repofull//\// })
          reponame=${parts[1]}
          assignment="${reponame/"-${{ github.actor }}"/""}"
          assignment="assignment_name=$assignment"

          wsfunction="wsfunction=local_gradeassignments_update_grade"
          wstoken="wstoken=${{ secrets.MOODLE_TOKEN }}"
          
          url="${{ vars.MOODLE_URL}}?${wstoken}&${wsfunction}&$assignment&$user&$points"
          curl $url

Create assignments
Create an assignment in GitHub Classroom.
Create a Moodle assignment and enter the name of the GitHub Classroom assignment in the custom field.

Auto grading
After the students accept the assignment they solve the assignment and push their code to GitHub. With every push the GitHub workflow runs the tests and calls the Moodle webservice to update the grade for this student.

Markdown 4698 bytes 624 words 88 lines Ln 31, Col 149HTML 3754 characters 602 words 71 paragraphs
