# Proctorio services

This is a collection of services that can be used to integrate Proctorio into your LTI application.

## Install this plugin

1. Clone this repository into the `local` directory of your Moodle installation.
2. Go to `Site administration > Notifications` and follow the on-screen instructions to complete the installation.

## Execute web service requests

To execute a web service request, you need to send a GET or POST request to the following URL:

    https://yourmoodle.com/local/proctorio/users.php

The request must contain the following parameters:

* `courseid`: The ID of the course you want to get the enrolled users from
* `teacherid`: The ID of the teacher you want to get the enrolled users from. This user must have the `moodle/course:viewparticipants` capability in the course.

## Return values

The web service will return a JSON object with the following structure:


```json	

   [
        {
            "id": 2,
            "firstname": "Admin",
            "lastname": "User"
        },
        {
            "id": 3,
            "firstname": "Test",
            "lastname": "User"
        }
    ]
```

If an error occurs, the web service will return a JSON object with the following structure:

```json
    {
        "error": true,
        "exception": {
            "message": "[error message]",
            "errorcode": "[internal code]",
            "backtrace": "[Error details (only in debug mode)]",
            "link": "[link error (only in debug mode)]",
            "moreinfourl": "[Url info (only in debug mode)]",
            "debuginfo": "[Debug info (only in debug mode)]"
        }
    }
```
