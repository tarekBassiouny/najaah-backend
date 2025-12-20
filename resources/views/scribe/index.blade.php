<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>XYZ-LMS API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://xyz-lms.test";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.6.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.6.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-POSTapi-webhooks-bunny">
                                <a href="#endpoints-POSTapi-webhooks-bunny">POST api/webhooks/bunny</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-send-otp">
                                <a href="#endpoints-POSTapi-v1-auth-send-otp">POST api/v1/auth/send-otp</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-verify">
                                <a href="#endpoints-POSTapi-v1-auth-verify">POST api/v1/auth/verify</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-refresh">
                                <a href="#endpoints-POSTapi-v1-auth-refresh">POST api/v1/auth/refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-auth-me">
                                <a href="#endpoints-GETapi-v1-auth-me">GET api/v1/auth/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-logout">
                                <a href="#endpoints-POSTapi-v1-auth-logout">POST api/v1/auth/logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-enrollments">
                                <a href="#endpoints-GETapi-v1-enrollments">GET api/v1/enrollments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses">
                                <a href="#endpoints-GETapi-v1-courses">GET api/v1/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id-">GET api/v1/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections">GET api/v1/courses/{course_id}/sections</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id-">GET api/v1/courses/{course_id}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--videos">
                                <a href="#endpoints-GETapi-v1-courses--course_id--videos">GET api/v1/courses/{course_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--videos--video_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--videos--video_id-">GET api/v1/courses/{course_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--videos">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--videos">GET api/v1/courses/{course_id}/sections/{section_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">GET api/v1/courses/{course_id}/sections/{section_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-instructors">
                                <a href="#endpoints-GETapi-v1-instructors">GET api/v1/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-instructors--instructor_id-">
                                <a href="#endpoints-GETapi-v1-instructors--instructor_id-">GET api/v1/instructors/{instructor_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--pdfs">
                                <a href="#endpoints-GETapi-v1-courses--course_id--pdfs">GET api/v1/courses/{course_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id--download">
                                <a href="#endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id--download">GET api/v1/courses/{course_id}/pdfs/{pdf_id}/download</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">
                                <a href="#endpoints-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">POST api/v1/courses/{course_id}/videos/{video_id}/playback/authorize</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-playback-sessions--session-">
                                <a href="#endpoints-PATCHapi-v1-playback-sessions--session-">PATCH api/v1/playback/sessions/{session}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-playback-sessions--session--end">
                                <a href="#endpoints-POSTapi-v1-playback-sessions--session--end">POST api/v1/playback/sessions/{session}/end</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-extra-view-requests">
                                <a href="#endpoints-GETapi-v1-extra-view-requests">GET api/v1/extra-view-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">
                                <a href="#endpoints-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">POST api/v1/courses/{course_id}/videos/{video_id}/extra-view-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-device-change-requests">
                                <a href="#endpoints-GETapi-v1-device-change-requests">GET api/v1/device-change-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-device-change-requests">
                                <a href="#endpoints-POSTapi-v1-device-change-requests">POST api/v1/device-change-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-auth-login">
                                <a href="#endpoints-POSTapi-v1-admin-auth-login">POST api/v1/admin/auth/login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-auth-password-reset">
                                <a href="#endpoints-POSTapi-v1-admin-auth-password-reset">POST api/v1/admin/auth/password/reset</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-auth-me">
                                <a href="#endpoints-GETapi-v1-admin-auth-me">GET api/v1/admin/auth/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-auth-refresh">
                                <a href="#endpoints-POSTapi-v1-admin-auth-refresh">POST api/v1/admin/auth/refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-auth-logout">
                                <a href="#endpoints-POSTapi-v1-admin-auth-logout">POST api/v1/admin/auth/logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-centers">
                                <a href="#endpoints-GETapi-v1-admin-centers">GET api/v1/admin/centers</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-centers">
                                <a href="#endpoints-POSTapi-v1-admin-centers">POST api/v1/admin/centers</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-centers--center-">
                                <a href="#endpoints-GETapi-v1-admin-centers--center-">GET api/v1/admin/centers/{center}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-centers--center-">
                                <a href="#endpoints-PUTapi-v1-admin-centers--center-">PUT api/v1/admin/centers/{center}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-centers--center-">
                                <a href="#endpoints-DELETEapi-v1-admin-centers--center-">DELETE api/v1/admin/centers/{center}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-centers--center--restore">
                                <a href="#endpoints-POSTapi-v1-admin-centers--center--restore">POST api/v1/admin/centers/{center}/restore</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-enrollments">
                                <a href="#endpoints-POSTapi-v1-admin-enrollments">POST api/v1/admin/enrollments</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-enrollments--enrollment_id-">
                                <a href="#endpoints-PUTapi-v1-admin-enrollments--enrollment_id-">PUT api/v1/admin/enrollments/{enrollment_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-enrollments--enrollment_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-enrollments--enrollment_id-">DELETE api/v1/admin/enrollments/{enrollment_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses">
                                <a href="#endpoints-GETapi-v1-admin-courses">GET api/v1/admin/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses">
                                <a href="#endpoints-POSTapi-v1-admin-courses">POST api/v1/admin/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id-">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id-">GET api/v1/admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-courses--course_id-">
                                <a href="#endpoints-PUTapi-v1-admin-courses--course_id-">PUT api/v1/admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id-">DELETE api/v1/admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--clone">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--clone">POST api/v1/admin/courses/{course_id}/clone</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--publish">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--publish">POST api/v1/admin/courses/{course_id}/publish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections">GET api/v1/admin/courses/{course_id}/sections</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course--sections">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course--sections">POST api/v1/admin/courses/{course}/sections</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-courses--course_id--sections-reorder">
                                <a href="#endpoints-PUTapi-v1-admin-courses--course_id--sections-reorder">PUT api/v1/admin/courses/{course_id}/sections/reorder</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id-">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections--section_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-courses--course--sections--section_id-">
                                <a href="#endpoints-PUTapi-v1-admin-courses--course--sections--section_id-">PUT api/v1/admin/courses/{course}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course--sections--section_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course--sections--section_id-">DELETE api/v1/admin/courses/{course}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--restore">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course--sections--section_id--restore">POST api/v1/admin/courses/{course}/sections/{section_id}/restore</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">
                                <a href="#endpoints-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">PATCH api/v1/admin/courses/{course_id}/sections/{section_id}/visibility</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course--sections-structure">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course--sections-structure">POST api/v1/admin/courses/{course}/sections/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-courses--course--sections--section_id--structure">
                                <a href="#endpoints-PUTapi-v1-admin-courses--course--sections--section_id--structure">PUT api/v1/admin/courses/{course}/sections/{section_id}/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course--sections--section_id--structure">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course--sections--section_id--structure">DELETE api/v1/admin/courses/{course}/sections/{section_id}/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos">GET api/v1/admin/courses/{course_id}/sections/{section_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">POST api/v1/admin/courses/{course_id}/sections/{section_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">DELETE api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">GET api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">POST api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">DELETE api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--publish">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course--sections--section_id--publish">POST api/v1/admin/courses/{course}/sections/{section_id}/publish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">POST api/v1/admin/courses/{course}/sections/{section_id}/unpublish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-videos">
                                <a href="#endpoints-GETapi-v1-admin-videos">GET api/v1/admin/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-video-upload-sessions">
                                <a href="#endpoints-GETapi-v1-admin-video-upload-sessions">GET api/v1/admin/video-upload-sessions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--videos">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--videos">POST api/v1/admin/courses/{course_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id--videos--video-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id--videos--video-">DELETE api/v1/admin/courses/{course_id}/videos/{video}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-video-uploads">
                                <a href="#endpoints-POSTapi-v1-admin-video-uploads">POST api/v1/admin/video-uploads</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">
                                <a href="#endpoints-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">PATCH api/v1/admin/video-uploads/{videoUploadSession_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-instructors">
                                <a href="#endpoints-GETapi-v1-admin-instructors">GET api/v1/admin/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-instructors">
                                <a href="#endpoints-POSTapi-v1-admin-instructors">POST api/v1/admin/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-instructors--id-">
                                <a href="#endpoints-GETapi-v1-admin-instructors--id-">GET api/v1/admin/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-instructors--id-">
                                <a href="#endpoints-PUTapi-v1-admin-instructors--id-">PUT api/v1/admin/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-instructors--id-">
                                <a href="#endpoints-DELETEapi-v1-admin-instructors--id-">DELETE api/v1/admin/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--instructors">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--instructors">POST api/v1/admin/courses/{course_id}/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">DELETE api/v1/admin/courses/{course_id}/instructors/{instructor_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-courses--course_id--pdfs">
                                <a href="#endpoints-POSTapi-v1-admin-courses--course_id--pdfs">POST api/v1/admin/courses/{course_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">
                                <a href="#endpoints-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">DELETE api/v1/admin/courses/{course_id}/pdfs/{pdf}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-pdfs">
                                <a href="#endpoints-POSTapi-v1-admin-pdfs">POST api/v1/admin/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-centers--center_id--settings">
                                <a href="#endpoints-GETapi-v1-admin-centers--center_id--settings">GET api/v1/admin/centers/{center_id}/settings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHapi-v1-admin-centers--center_id--settings">
                                <a href="#endpoints-PATCHapi-v1-admin-centers--center_id--settings">PATCH api/v1/admin/centers/{center_id}/settings</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-settings-preview">
                                <a href="#endpoints-GETapi-v1-admin-settings-preview">GET api/v1/admin/settings/preview</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-audit-logs">
                                <a href="#endpoints-GETapi-v1-admin-audit-logs">GET api/v1/admin/audit-logs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-extra-view-requests">
                                <a href="#endpoints-GETapi-v1-admin-extra-view-requests">GET api/v1/admin/extra-view-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">
                                <a href="#endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">POST api/v1/admin/extra-view-requests/{extraViewRequest_id}/approve</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">
                                <a href="#endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">POST api/v1/admin/extra-view-requests/{extraViewRequest_id}/reject</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-device-change-requests">
                                <a href="#endpoints-GETapi-v1-admin-device-change-requests">GET api/v1/admin/device-change-requests</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">
                                <a href="#endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">POST api/v1/admin/device-change-requests/{deviceChangeRequest_id}/approve</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">
                                <a href="#endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">POST api/v1/admin/device-change-requests/{deviceChangeRequest_id}/reject</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-roles">
                                <a href="#endpoints-GETapi-v1-admin-roles">GET api/v1/admin/roles</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-roles">
                                <a href="#endpoints-POSTapi-v1-admin-roles">POST api/v1/admin/roles</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-roles--role_id-">
                                <a href="#endpoints-PUTapi-v1-admin-roles--role_id-">PUT api/v1/admin/roles/{role_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-roles--role_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-roles--role_id-">DELETE api/v1/admin/roles/{role_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-roles--role_id--permissions">
                                <a href="#endpoints-PUTapi-v1-admin-roles--role_id--permissions">PUT api/v1/admin/roles/{role_id}/permissions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-permissions">
                                <a href="#endpoints-GETapi-v1-admin-permissions">GET api/v1/admin/permissions</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-users">
                                <a href="#endpoints-GETapi-v1-admin-users">GET api/v1/admin/users</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-users">
                                <a href="#endpoints-POSTapi-v1-admin-users">POST api/v1/admin/users</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-users--user_id-">
                                <a href="#endpoints-PUTapi-v1-admin-users--user_id-">PUT api/v1/admin/users/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-users--user_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-users--user_id-">DELETE api/v1/admin/users/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-users--user_id--roles">
                                <a href="#endpoints-PUTapi-v1-admin-users--user_id--roles">PUT api/v1/admin/users/{user_id}/roles</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-admin-students">
                                <a href="#endpoints-GETapi-v1-admin-students">GET api/v1/admin/students</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-admin-students--user_id-">
                                <a href="#endpoints-PUTapi-v1-admin-students--user_id-">PUT api/v1/admin/students/{user_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-admin-students">
                                <a href="#endpoints-POSTapi-v1-admin-students">POST api/v1/admin/students</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-admin-students--user_id-">
                                <a href="#endpoints-DELETEapi-v1-admin-students--user_id-">DELETE api/v1/admin/students/{user_id}</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: December 20, 2025</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://xyz-lms.test</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer your-token"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Use a valid JWT access token.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-POSTapi-webhooks-bunny">POST api/webhooks/bunny</h2>

<p>
</p>



<span id="example-requests-POSTapi-webhooks-bunny">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/webhooks/bunny" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/webhooks/bunny"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-webhooks-bunny">
</span>
<span id="execution-results-POSTapi-webhooks-bunny" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-webhooks-bunny"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-webhooks-bunny"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-webhooks-bunny" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-webhooks-bunny">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-webhooks-bunny" data-method="POST"
      data-path="api/webhooks/bunny"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-webhooks-bunny', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-webhooks-bunny"
                    onclick="tryItOut('POSTapi-webhooks-bunny');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-webhooks-bunny"
                    onclick="cancelTryOut('POSTapi-webhooks-bunny');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-webhooks-bunny"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/webhooks/bunny</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-webhooks-bunny"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-webhooks-bunny"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-webhooks-bunny"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-auth-send-otp">POST api/v1/auth/send-otp</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-send-otp">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/auth/send-otp" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"phone\": \"01234567890\",
    \"country_code\": \"+2\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/auth/send-otp"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "phone": "01234567890",
    "country_code": "+2"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-send-otp">
</span>
<span id="execution-results-POSTapi-v1-auth-send-otp" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-send-otp"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-send-otp"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-send-otp" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-send-otp">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-send-otp" data-method="POST"
      data-path="api/v1/auth/send-otp"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-send-otp', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-send-otp"
                    onclick="tryItOut('POSTapi-v1-auth-send-otp');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-send-otp"
                    onclick="cancelTryOut('POSTapi-v1-auth-send-otp');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-send-otp"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/send-otp</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-send-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-send-otp"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-auth-send-otp"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-auth-send-otp"
               value="01234567890"
               data-component="body">
    <br>
<p>The full phone number including country code. Example: <code>01234567890</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_code"                data-endpoint="POSTapi-v1-auth-send-otp"
               value="+2"
               data-component="body">
    <br>
<p>The country dialing code. Must not be greater than 5 characters. Example: <code>+2</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-auth-verify">POST api/v1/auth/verify</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-verify">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/auth/verify" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"otp\": \"123456\",
    \"token\": \"e9f9c844-0e58-4aab-8db0-0c5b0aeb8d99\",
    \"device_uuid\": \"device-123\",
    \"device_name\": \"iPhone 15\",
    \"device_os\": \"iOS 17\",
    \"device_type\": \"mobile\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/auth/verify"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "otp": "123456",
    "token": "e9f9c844-0e58-4aab-8db0-0c5b0aeb8d99",
    "device_uuid": "device-123",
    "device_name": "iPhone 15",
    "device_os": "iOS 17",
    "device_type": "mobile"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-verify">
</span>
<span id="execution-results-POSTapi-v1-auth-verify" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-verify"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-verify"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-verify" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-verify">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-verify" data-method="POST"
      data-path="api/v1/auth/verify"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-verify', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-verify"
                    onclick="tryItOut('POSTapi-v1-auth-verify');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-verify"
                    onclick="cancelTryOut('POSTapi-v1-auth-verify');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-verify"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/verify</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-verify"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-auth-verify"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>otp</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="otp"                data-endpoint="POSTapi-v1-auth-verify"
               value="123456"
               data-component="body">
    <br>
<p>The OTP code received by the user. Example: <code>123456</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-v1-auth-verify"
               value="e9f9c844-0e58-4aab-8db0-0c5b0aeb8d99"
               data-component="body">
    <br>
<p>The OTP token returned from send-otp. Example: <code>e9f9c844-0e58-4aab-8db0-0c5b0aeb8d99</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_uuid</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_uuid"                data-endpoint="POSTapi-v1-auth-verify"
               value="device-123"
               data-component="body">
    <br>
<p>The unique device identifier. Example: <code>device-123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_name"                data-endpoint="POSTapi-v1-auth-verify"
               value="iPhone 15"
               data-component="body">
    <br>
<p>The human readable device name. Example: <code>iPhone 15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_os</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_os"                data-endpoint="POSTapi-v1-auth-verify"
               value="iOS 17"
               data-component="body">
    <br>
<p>The OS or platform info. Example: <code>iOS 17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_type"                data-endpoint="POSTapi-v1-auth-verify"
               value="mobile"
               data-component="body">
    <br>
<p>The type of device. Example: <code>mobile</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-auth-refresh">POST api/v1/auth/refresh</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/auth/refresh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"refresh_token\": \"jwt-refresh-token\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/auth/refresh"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "refresh_token": "jwt-refresh-token"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-refresh">
</span>
<span id="execution-results-POSTapi-v1-auth-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-refresh" data-method="POST"
      data-path="api/v1/auth/refresh"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-refresh"
                    onclick="tryItOut('POSTapi-v1-auth-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-refresh"
                    onclick="cancelTryOut('POSTapi-v1-auth-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-auth-refresh"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>refresh_token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="refresh_token"                data-endpoint="POSTapi-v1-auth-refresh"
               value="jwt-refresh-token"
               data-component="body">
    <br>
<p>The refresh token issued at login. Example: <code>jwt-refresh-token</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-auth-me">GET api/v1/auth/me</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-auth-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/auth/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/auth/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-auth-me">
    </span>
<span id="execution-results-GETapi-v1-auth-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-auth-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-auth-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-auth-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-auth-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-auth-me" data-method="GET"
      data-path="api/v1/auth/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-auth-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-auth-me"
                    onclick="tryItOut('GETapi-v1-auth-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-auth-me"
                    onclick="cancelTryOut('GETapi-v1-auth-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-auth-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/auth/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-auth-me"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-auth-logout">POST api/v1/auth/logout</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-auth-logout">
</span>
<span id="execution-results-POSTapi-v1-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-auth-logout" data-method="POST"
      data-path="api/v1/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-auth-logout"
                    onclick="tryItOut('POSTapi-v1-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-auth-logout"
                    onclick="cancelTryOut('POSTapi-v1-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-auth-logout"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-enrollments">GET api/v1/enrollments</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-enrollments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/enrollments?per_page=15" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/enrollments"
);

const params = {
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-enrollments">
    </span>
<span id="execution-results-GETapi-v1-enrollments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-enrollments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-enrollments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-enrollments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-enrollments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-enrollments" data-method="GET"
      data-path="api/v1/enrollments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-enrollments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-enrollments"
                    onclick="tryItOut('GETapi-v1-enrollments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-enrollments"
                    onclick="cancelTryOut('GETapi-v1-enrollments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-enrollments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/enrollments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-enrollments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-enrollments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-enrollments"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-enrollments"
               value="15"
               data-component="query">
    <br>
<p>Items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="endpoints-GETapi-v1-courses">GET api/v1/courses</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses?per_page=15" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses"
);

const params = {
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses">
    </span>
<span id="execution-results-GETapi-v1-courses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses" data-method="GET"
      data-path="api/v1/courses"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses"
                    onclick="tryItOut('GETapi-v1-courses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses"
                    onclick="cancelTryOut('GETapi-v1-courses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-courses"
               value="15"
               data-component="query">
    <br>
<p>Items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id-">GET api/v1/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections">GET api/v1/courses/{course_id}/sections</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections--section_id-">GET api/v1/courses/{course_id}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections--section_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections--section_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections--section_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections--section_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--videos">GET api/v1/courses/{course_id}/videos</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/videos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--videos">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--videos" data-method="GET"
      data-path="api/v1/courses/{course_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--videos"
                    onclick="tryItOut('GETapi-v1-courses--course_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--videos"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--videos--video_id-">GET api/v1/courses/{course_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/videos/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--videos--video_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--videos--video_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--videos--video_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--videos--video_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--videos--video_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="GETapi-v1-courses--course_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections--section_id--videos">GET api/v1/courses/{course_id}/sections/{section_id}/videos</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections--section_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections/1/videos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections--section_id--videos">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections--section_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections--section_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections--section_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections--section_id--videos" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections/{section_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections--section_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections--section_id--videos"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections--section_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections--section_id--videos"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections--section_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections--section_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections/{section_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">GET api/v1/courses/{course_id}/sections/{section_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections/1/videos/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections/{section_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections--section_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections--section_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections--section_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections/{section_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-instructors">GET api/v1/instructors</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/instructors?per_page=15&amp;page=1&amp;search=Sara" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/instructors"
);

const params = {
    "per_page": "15",
    "page": "1",
    "search": "Sara",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-instructors">
    </span>
<span id="execution-results-GETapi-v1-instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-instructors" data-method="GET"
      data-path="api/v1/instructors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-instructors"
                    onclick="tryItOut('GETapi-v1-instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-instructors"
                    onclick="cancelTryOut('GETapi-v1-instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-instructors"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-instructors"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-instructors"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-instructors"
               value="Sara"
               data-component="query">
    <br>
<p>Search instructors by name. Example: <code>Sara</code></p>
            </div>
                </form>

                    <h2 id="endpoints-GETapi-v1-instructors--instructor_id-">GET api/v1/instructors/{instructor_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-instructors--instructor_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/instructors/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-instructors--instructor_id-">
    </span>
<span id="execution-results-GETapi-v1-instructors--instructor_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-instructors--instructor_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-instructors--instructor_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-instructors--instructor_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-instructors--instructor_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-instructors--instructor_id-" data-method="GET"
      data-path="api/v1/instructors/{instructor_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-instructors--instructor_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-instructors--instructor_id-"
                    onclick="tryItOut('GETapi-v1-instructors--instructor_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-instructors--instructor_id-"
                    onclick="cancelTryOut('GETapi-v1-instructors--instructor_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-instructors--instructor_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/instructors/{instructor_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-instructors--instructor_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-instructors--instructor_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-instructors--instructor_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>instructor_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="instructor_id"                data-endpoint="GETapi-v1-instructors--instructor_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--pdfs">GET api/v1/courses/{course_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/pdfs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--pdfs">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--pdfs" data-method="GET"
      data-path="api/v1/courses/{course_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--pdfs"
                    onclick="tryItOut('GETapi-v1-courses--course_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--pdfs"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/pdfs/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--pdfs--pdf_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--pdfs--pdf_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--pdfs--pdf_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id--download">GET api/v1/courses/{course_id}/pdfs/{pdf_id}/download</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--pdfs--pdf_id--download">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/pdfs/1/download" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/pdfs/1/download"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--pdfs--pdf_id--download">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--pdfs--pdf_id--download" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--pdfs--pdf_id--download"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--pdfs--pdf_id--download"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--pdfs--pdf_id--download" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--pdfs--pdf_id--download">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--pdfs--pdf_id--download" data-method="GET"
      data-path="api/v1/courses/{course_id}/pdfs/{pdf_id}/download"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--pdfs--pdf_id--download', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--pdfs--pdf_id--download"
                    onclick="tryItOut('GETapi-v1-courses--course_id--pdfs--pdf_id--download');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--pdfs--pdf_id--download"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--pdfs--pdf_id--download');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--pdfs--pdf_id--download"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/pdfs/{pdf_id}/download</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id--download"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id--download"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id--download"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id--download"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="GETapi-v1-courses--course_id--pdfs--pdf_id--download"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections--section_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections/1/pdfs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections--section_id--pdfs">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections--section_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections--section_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections--section_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections--section_id--pdfs" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections/{section_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections--section_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections--section_id--pdfs"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections--section_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections--section_id--pdfs"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections--section_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections--section_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections/{section_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses/1/sections/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/sections/1/pdfs/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">
    </span>
<span id="execution-results-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-" data-method="GET"
      data-path="api/v1/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="tryItOut('GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">POST api/v1/courses/{course_id}/videos/{video_id}/playback/authorize</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/courses/1/videos/1/playback/authorize" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"device_id\": \"device-123\",
    \"section_id\": 5
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/videos/1/playback/authorize"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "device_id": "device-123",
    "section_id": 5
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">
</span>
<span id="execution-results-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize" data-method="POST"
      data-path="api/v1/courses/{course_id}/videos/{video_id}/playback/authorize"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-courses--course_id--videos--video_id--playback-authorize', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
                    onclick="tryItOut('POSTapi-v1-courses--course_id--videos--video_id--playback-authorize');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
                    onclick="cancelTryOut('POSTapi-v1-courses--course_id--videos--video_id--playback-authorize');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/courses/{course_id}/videos/{video_id}/playback/authorize</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="device_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="device-123"
               data-component="body">
    <br>
<p>Identifier of the requesting device. Example: <code>device-123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--playback-authorize"
               value="5"
               data-component="body">
    <br>
<p>Optional section context for the video. The <code>id</code> of an existing record in the sections table. Example: <code>5</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PATCHapi-v1-playback-sessions--session-">PATCH api/v1/playback/sessions/{session}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-playback-sessions--session-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://xyz-lms.test/api/v1/playback/sessions/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"progress_percent\": 45
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/playback/sessions/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "progress_percent": 45
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-playback-sessions--session-">
</span>
<span id="execution-results-PATCHapi-v1-playback-sessions--session-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-playback-sessions--session-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-playback-sessions--session-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-playback-sessions--session-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-playback-sessions--session-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-playback-sessions--session-" data-method="PATCH"
      data-path="api/v1/playback/sessions/{session}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-playback-sessions--session-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-playback-sessions--session-"
                    onclick="tryItOut('PATCHapi-v1-playback-sessions--session-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-playback-sessions--session-"
                    onclick="cancelTryOut('PATCHapi-v1-playback-sessions--session-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-playback-sessions--session-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/playback/sessions/{session}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-playback-sessions--session-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-playback-sessions--session-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PATCHapi-v1-playback-sessions--session-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>session</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="session"                data-endpoint="PATCHapi-v1-playback-sessions--session-"
               value="architecto"
               data-component="url">
    <br>
<p>The session. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>progress_percent</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="progress_percent"                data-endpoint="PATCHapi-v1-playback-sessions--session-"
               value="45"
               data-component="body">
    <br>
<p>Playback progress percentage (0-100). Must be at least 0. Must not be greater than 100. Example: <code>45</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-playback-sessions--session--end">POST api/v1/playback/sessions/{session}/end</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-playback-sessions--session--end">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/playback/sessions/architecto/end" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"progress_percent\": 100
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/playback/sessions/architecto/end"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "progress_percent": 100
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-playback-sessions--session--end">
</span>
<span id="execution-results-POSTapi-v1-playback-sessions--session--end" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-playback-sessions--session--end"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-playback-sessions--session--end"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-playback-sessions--session--end" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-playback-sessions--session--end">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-playback-sessions--session--end" data-method="POST"
      data-path="api/v1/playback/sessions/{session}/end"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-playback-sessions--session--end', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-playback-sessions--session--end"
                    onclick="tryItOut('POSTapi-v1-playback-sessions--session--end');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-playback-sessions--session--end"
                    onclick="cancelTryOut('POSTapi-v1-playback-sessions--session--end');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-playback-sessions--session--end"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/playback/sessions/{session}/end</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-playback-sessions--session--end"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-playback-sessions--session--end"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-playback-sessions--session--end"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>session</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="session"                data-endpoint="POSTapi-v1-playback-sessions--session--end"
               value="architecto"
               data-component="url">
    <br>
<p>The session. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>progress_percent</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="progress_percent"                data-endpoint="POSTapi-v1-playback-sessions--session--end"
               value="100"
               data-component="body">
    <br>
<p>Final progress percentage when ending the session. Must be at least 0. Must not be greater than 100. Example: <code>100</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-extra-view-requests">GET api/v1/extra-view-requests</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-extra-view-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/extra-view-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/extra-view-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-extra-view-requests">
    </span>
<span id="execution-results-GETapi-v1-extra-view-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-extra-view-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-extra-view-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-extra-view-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-extra-view-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-extra-view-requests" data-method="GET"
      data-path="api/v1/extra-view-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-extra-view-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-extra-view-requests"
                    onclick="tryItOut('GETapi-v1-extra-view-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-extra-view-requests"
                    onclick="cancelTryOut('GETapi-v1-extra-view-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-extra-view-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/extra-view-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-extra-view-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">POST api/v1/courses/{course_id}/videos/{video_id}/extra-view-requests</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/courses/1/videos/1/extra-view-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"reason\": \"Need to review before exam\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/videos/1/extra-view-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "reason": "Need to review before exam"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">
</span>
<span id="execution-results-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests" data-method="POST"
      data-path="api/v1/courses/{course_id}/videos/{video_id}/extra-view-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
                    onclick="tryItOut('POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
                    onclick="cancelTryOut('POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/courses/{course_id}/videos/{video_id}/extra-view-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-courses--course_id--videos--video_id--extra-view-requests"
               value="Need to review before exam"
               data-component="body">
    <br>
<p>Optional reason for requesting extra views. Must not be greater than 1000 characters. Example: <code>Need to review before exam</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-device-change-requests">GET api/v1/device-change-requests</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-device-change-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/device-change-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/device-change-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-device-change-requests">
    </span>
<span id="execution-results-GETapi-v1-device-change-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-device-change-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-device-change-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-device-change-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-device-change-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-device-change-requests" data-method="GET"
      data-path="api/v1/device-change-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-device-change-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-device-change-requests"
                    onclick="tryItOut('GETapi-v1-device-change-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-device-change-requests"
                    onclick="cancelTryOut('GETapi-v1-device-change-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-device-change-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/device-change-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-device-change-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-device-change-requests">POST api/v1/device-change-requests</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-device-change-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/device-change-requests" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"new_device_id\": \"device-xyz\",
    \"model\": \"iPhone 15\",
    \"os_version\": \"iOS 17\",
    \"reason\": \"Upgraded phone\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/device-change-requests"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "new_device_id": "device-xyz",
    "model": "iPhone 15",
    "os_version": "iOS 17",
    "reason": "Upgraded phone"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-device-change-requests">
</span>
<span id="execution-results-POSTapi-v1-device-change-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-device-change-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-device-change-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-device-change-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-device-change-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-device-change-requests" data-method="POST"
      data-path="api/v1/device-change-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-device-change-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-device-change-requests"
                    onclick="tryItOut('POSTapi-v1-device-change-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-device-change-requests"
                    onclick="cancelTryOut('POSTapi-v1-device-change-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-device-change-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/device-change-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-device-change-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>new_device_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="new_device_id"                data-endpoint="POSTapi-v1-device-change-requests"
               value="device-xyz"
               data-component="body">
    <br>
<p>Identifier of the requested new device. Must not be greater than 255 characters. Example: <code>device-xyz</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>model</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="model"                data-endpoint="POSTapi-v1-device-change-requests"
               value="iPhone 15"
               data-component="body">
    <br>
<p>Device model name. Must not be greater than 255 characters. Example: <code>iPhone 15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>os_version</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="os_version"                data-endpoint="POSTapi-v1-device-change-requests"
               value="iOS 17"
               data-component="body">
    <br>
<p>Operating system version. Must not be greater than 255 characters. Example: <code>iOS 17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="reason"                data-endpoint="POSTapi-v1-device-change-requests"
               value="Upgraded phone"
               data-component="body">
    <br>
<p>Optional reason for requesting device change. Must not be greater than 1000 characters. Example: <code>Upgraded phone</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-auth-login">POST api/v1/admin/auth/login</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/auth/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"email\": \"admin@example.com\",
    \"password\": \"admin123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/auth/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "email": "admin@example.com",
    "password": "admin123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-auth-login">
</span>
<span id="execution-results-POSTapi-v1-admin-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-auth-login" data-method="POST"
      data-path="api/v1/admin/auth/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-auth-login"
                    onclick="tryItOut('POSTapi-v1-admin-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-auth-login"
                    onclick="cancelTryOut('POSTapi-v1-admin-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-auth-login"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-auth-login"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-auth-login"
               value="admin@example.com"
               data-component="body">
    <br>
<p>Admin email address. Must be a valid email address. Example: <code>admin@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-admin-auth-login"
               value="admin123"
               data-component="body">
    <br>
<p>Admin password. Example: <code>admin123</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-auth-password-reset">POST api/v1/admin/auth/password/reset</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-auth-password-reset">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/auth/password/reset" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"token\": \"reset-token\",
    \"email\": \"admin@example.com\",
    \"password\": \"newpassword123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/auth/password/reset"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "token": "reset-token",
    "email": "admin@example.com",
    "password": "newpassword123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-auth-password-reset">
</span>
<span id="execution-results-POSTapi-v1-admin-auth-password-reset" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-auth-password-reset"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-auth-password-reset"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-auth-password-reset" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-auth-password-reset">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-auth-password-reset" data-method="POST"
      data-path="api/v1/admin/auth/password/reset"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-auth-password-reset', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-auth-password-reset"
                    onclick="tryItOut('POSTapi-v1-admin-auth-password-reset');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-auth-password-reset"
                    onclick="cancelTryOut('POSTapi-v1-admin-auth-password-reset');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-auth-password-reset"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/auth/password/reset</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>token</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="token"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="reset-token"
               data-component="body">
    <br>
<p>Password reset token. Example: <code>reset-token</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="admin@example.com"
               data-component="body">
    <br>
<p>Admin email address. Must be a valid email address. Example: <code>admin@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-admin-auth-password-reset"
               value="newpassword123"
               data-component="body">
    <br>
<p>New password. Must be at least 8 characters. Example: <code>newpassword123</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-auth-me">GET api/v1/admin/auth/me</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-auth-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/auth/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/auth/me"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-auth-me">
    </span>
<span id="execution-results-GETapi-v1-admin-auth-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-auth-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-auth-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-auth-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-auth-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-auth-me" data-method="GET"
      data-path="api/v1/admin/auth/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-auth-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-auth-me"
                    onclick="tryItOut('GETapi-v1-admin-auth-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-auth-me"
                    onclick="cancelTryOut('GETapi-v1-admin-auth-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-auth-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/auth/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-auth-me"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-auth-me"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-auth-refresh">POST api/v1/admin/auth/refresh</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-auth-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/auth/refresh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/auth/refresh"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-auth-refresh">
</span>
<span id="execution-results-POSTapi-v1-admin-auth-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-auth-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-auth-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-auth-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-auth-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-auth-refresh" data-method="POST"
      data-path="api/v1/admin/auth/refresh"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-auth-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-auth-refresh"
                    onclick="tryItOut('POSTapi-v1-admin-auth-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-auth-refresh"
                    onclick="cancelTryOut('POSTapi-v1-admin-auth-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-auth-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/auth/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-auth-refresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-auth-refresh"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-auth-logout">POST api/v1/admin/auth/logout</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/auth/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-auth-logout">
</span>
<span id="execution-results-POSTapi-v1-admin-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-auth-logout" data-method="POST"
      data-path="api/v1/admin/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-auth-logout"
                    onclick="tryItOut('POSTapi-v1-admin-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-auth-logout"
                    onclick="cancelTryOut('POSTapi-v1-admin-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-auth-logout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-auth-logout"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-admin-centers">GET api/v1/admin/centers</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-centers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/centers?per_page=15&amp;page=1&amp;slug=center-1&amp;type=1&amp;search=Academy" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers"
);

const params = {
    "per_page": "15",
    "page": "1",
    "slug": "center-1",
    "type": "1",
    "search": "Academy",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-centers">
    </span>
<span id="execution-results-GETapi-v1-admin-centers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-centers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-centers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-centers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-centers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-centers" data-method="GET"
      data-path="api/v1/admin/centers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-centers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-centers"
                    onclick="tryItOut('GETapi-v1-admin-centers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-centers"
                    onclick="cancelTryOut('GETapi-v1-admin-centers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-centers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/centers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-centers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-centers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-centers"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-centers"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-centers"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="GETapi-v1-admin-centers"
               value="center-1"
               data-component="query">
    <br>
<p>Filter centers by slug. Example: <code>center-1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="type"                data-endpoint="GETapi-v1-admin-centers"
               value="1"
               data-component="query">
    <br>
<p>Filter centers by type. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-admin-centers"
               value="Academy"
               data-component="query">
    <br>
<p>Search centers by name. Example: <code>Academy</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-centers">POST api/v1/admin/centers</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-centers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/centers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"slug\": \"center-01\",
    \"type\": 0,
    \"name\": \"Center Name\",
    \"name_translations\": {
        \"en\": \"Center Name\",
        \"ar\": \"اسم المركز\"
    },
    \"description_translations\": {
        \"en\": \"Description\"
    },
    \"logo_url\": \"https:\\/\\/example.com\\/logo.png\",
    \"primary_color\": \"#000000\",
    \"default_view_limit\": 3,
    \"allow_extra_view_requests\": false,
    \"pdf_download_permission\": false,
    \"device_limit\": 1,
    \"settings\": {
        \"pdf_download_permission\": true
    },
    \"owner_user_id\": 10,
    \"owner\": {
        \"name\": \"Owner Name\",
        \"email\": \"owner@example.com\",
        \"phone\": \"+1234567890\"
    },
    \"owner_role\": \"center_owner\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "slug": "center-01",
    "type": 0,
    "name": "Center Name",
    "name_translations": {
        "en": "Center Name",
        "ar": "اسم المركز"
    },
    "description_translations": {
        "en": "Description"
    },
    "logo_url": "https:\/\/example.com\/logo.png",
    "primary_color": "#000000",
    "default_view_limit": 3,
    "allow_extra_view_requests": false,
    "pdf_download_permission": false,
    "device_limit": 1,
    "settings": {
        "pdf_download_permission": true
    },
    "owner_user_id": 10,
    "owner": {
        "name": "Owner Name",
        "email": "owner@example.com",
        "phone": "+1234567890"
    },
    "owner_role": "center_owner"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-centers">
</span>
<span id="execution-results-POSTapi-v1-admin-centers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-centers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-centers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-centers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-centers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-centers" data-method="POST"
      data-path="api/v1/admin/centers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-centers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-centers"
                    onclick="tryItOut('POSTapi-v1-admin-centers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-centers"
                    onclick="cancelTryOut('POSTapi-v1-admin-centers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-centers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/centers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-centers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-centers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-centers"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="POSTapi-v1-admin-centers"
               value="center-01"
               data-component="body">
    <br>
<p>Unique, immutable center slug. Must contain only letters, numbers, dashes and underscores. Must not be greater than 255 characters. Example: <code>center-01</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="type"                data-endpoint="POSTapi-v1-admin-centers"
               value="0"
               data-component="body">
    <br>
<p>Center type identifier. Example: <code>0</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-admin-centers"
               value="Center Name"
               data-component="body">
    <br>
<p>Center name when translations are not provided. This field is required when <code>name_translations</code> is not present. Must not be greater than 255 characters. Example: <code>Center Name</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name_translations"                data-endpoint="POSTapi-v1-admin-centers"
               value=""
               data-component="body">
    <br>
<p>Localized center name. This field is required when <code>name</code> is not present.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations"                data-endpoint="POSTapi-v1-admin-centers"
               value=""
               data-component="body">
    <br>
<p>Localized center description.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>logo_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="logo_url"                data-endpoint="POSTapi-v1-admin-centers"
               value="https://example.com/logo.png"
               data-component="body">
    <br>
<p>Logo URL for the center. Example: <code>https://example.com/logo.png</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>primary_color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="primary_color"                data-endpoint="POSTapi-v1-admin-centers"
               value="#000000"
               data-component="body">
    <br>
<p>Primary branding color. Example: <code>#000000</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>default_view_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="default_view_limit"                data-endpoint="POSTapi-v1-admin-centers"
               value="3"
               data-component="body">
    <br>
<p>Default view limit for videos. Must be at least 0. Example: <code>3</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>allow_extra_view_requests</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-admin-centers" style="display: none">
            <input type="radio" name="allow_extra_view_requests"
                   value="true"
                   data-endpoint="POSTapi-v1-admin-centers"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-admin-centers" style="display: none">
            <input type="radio" name="allow_extra_view_requests"
                   value="false"
                   data-endpoint="POSTapi-v1-admin-centers"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether students can request extra views. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdf_download_permission</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-admin-centers" style="display: none">
            <input type="radio" name="pdf_download_permission"
                   value="true"
                   data-endpoint="POSTapi-v1-admin-centers"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-admin-centers" style="display: none">
            <input type="radio" name="pdf_download_permission"
                   value="false"
                   data-endpoint="POSTapi-v1-admin-centers"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether PDF downloads are allowed by default. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="device_limit"                data-endpoint="POSTapi-v1-admin-centers"
               value="1"
               data-component="body">
    <br>
<p>Maximum active devices per student. Must be at least 1. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings"                data-endpoint="POSTapi-v1-admin-centers"
               value=""
               data-component="body">
    <br>
<p>Optional center settings payload.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>owner_user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="owner_user_id"                data-endpoint="POSTapi-v1-admin-centers"
               value="10"
               data-component="body">
    <br>
<p>Existing user ID to assign as the owner. This field is required when <code>owner</code> is not present. The <code>id</code> of an existing record in the users table. Example: <code>10</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>owner</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Owner details when creating a new owner user. This field is required when <code>owner_user_id</code> is not present.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="owner.name"                data-endpoint="POSTapi-v1-admin-centers"
               value="Owner Name"
               data-component="body">
    <br>
<p>Owner name. This field is required when <code>owner_user_id</code> is not present. Must not be greater than 255 characters. Example: <code>Owner Name</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="owner.email"                data-endpoint="POSTapi-v1-admin-centers"
               value="owner@example.com"
               data-component="body">
    <br>
<p>Owner email address. This field is required when <code>owner_user_id</code> is not present. Must be a valid email address. Must not be greater than 255 characters. Example: <code>owner@example.com</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="owner.phone"                data-endpoint="POSTapi-v1-admin-centers"
               value="+1234567890"
               data-component="body">
    <br>
<p>Owner phone number. Must not be greater than 50 characters. Example: <code>+1234567890</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>owner_role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="owner_role"                data-endpoint="POSTapi-v1-admin-centers"
               value="center_owner"
               data-component="body">
    <br>
<p>Optional role name to assign to the owner. Example: <code>center_owner</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-centers--center-">GET api/v1/admin/centers/{center}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-centers--center-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/centers/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-centers--center-">
    </span>
<span id="execution-results-GETapi-v1-admin-centers--center-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-centers--center-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-centers--center-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-centers--center-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-centers--center-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-centers--center-" data-method="GET"
      data-path="api/v1/admin/centers/{center}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-centers--center-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-centers--center-"
                    onclick="tryItOut('GETapi-v1-admin-centers--center-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-centers--center-"
                    onclick="cancelTryOut('GETapi-v1-admin-centers--center-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-centers--center-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/centers/{center}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-centers--center-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center"                data-endpoint="GETapi-v1-admin-centers--center-"
               value="architecto"
               data-component="url">
    <br>
<p>The center. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-centers--center-">PUT api/v1/admin/centers/{center}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-centers--center-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/centers/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"slug\": \"center-01\",
    \"type\": 0,
    \"name_translations\": {
        \"en\": \"Updated Name\"
    },
    \"description_translations\": {
        \"en\": \"Updated description\"
    },
    \"logo_url\": \"https:\\/\\/example.com\\/logo.png\",
    \"primary_color\": \"#123456\",
    \"default_view_limit\": 5,
    \"allow_extra_view_requests\": false,
    \"pdf_download_permission\": false,
    \"device_limit\": 2,
    \"settings\": {
        \"pdf_download_permission\": false
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "slug": "center-01",
    "type": 0,
    "name_translations": {
        "en": "Updated Name"
    },
    "description_translations": {
        "en": "Updated description"
    },
    "logo_url": "https:\/\/example.com\/logo.png",
    "primary_color": "#123456",
    "default_view_limit": 5,
    "allow_extra_view_requests": false,
    "pdf_download_permission": false,
    "device_limit": 2,
    "settings": {
        "pdf_download_permission": false
    }
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-centers--center-">
</span>
<span id="execution-results-PUTapi-v1-admin-centers--center-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-centers--center-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-centers--center-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-centers--center-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-centers--center-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-centers--center-" data-method="PUT"
      data-path="api/v1/admin/centers/{center}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-centers--center-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-centers--center-"
                    onclick="tryItOut('PUTapi-v1-admin-centers--center-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-centers--center-"
                    onclick="cancelTryOut('PUTapi-v1-admin-centers--center-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-centers--center-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/centers/{center}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="architecto"
               data-component="url">
    <br>
<p>The center. Example: <code>architecto</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="center-01"
               data-component="body">
    <br>
<p>Unique, immutable center slug. Must contain only letters, numbers, dashes and underscores. Must not be greater than 255 characters. Example: <code>center-01</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>type</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="type"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="0"
               data-component="body">
    <br>
<p>Center type identifier. Example: <code>0</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name_translations"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value=""
               data-component="body">
    <br>
<p>Localized center name.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value=""
               data-component="body">
    <br>
<p>Localized description.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>logo_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="logo_url"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="https://example.com/logo.png"
               data-component="body">
    <br>
<p>Logo URL. Example: <code>https://example.com/logo.png</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>primary_color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="primary_color"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="#123456"
               data-component="body">
    <br>
<p>Primary branding color. Example: <code>#123456</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>default_view_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="default_view_limit"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="5"
               data-component="body">
    <br>
<p>Default view limit for videos. Must be at least 0. Example: <code>5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>allow_extra_view_requests</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-admin-centers--center-" style="display: none">
            <input type="radio" name="allow_extra_view_requests"
                   value="true"
                   data-endpoint="PUTapi-v1-admin-centers--center-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-admin-centers--center-" style="display: none">
            <input type="radio" name="allow_extra_view_requests"
                   value="false"
                   data-endpoint="PUTapi-v1-admin-centers--center-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether extra view requests are allowed. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdf_download_permission</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PUTapi-v1-admin-centers--center-" style="display: none">
            <input type="radio" name="pdf_download_permission"
                   value="true"
                   data-endpoint="PUTapi-v1-admin-centers--center-"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PUTapi-v1-admin-centers--center-" style="display: none">
            <input type="radio" name="pdf_download_permission"
                   value="false"
                   data-endpoint="PUTapi-v1-admin-centers--center-"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether PDF downloads are allowed by default. Example: <code>false</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>device_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="device_limit"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value="2"
               data-component="body">
    <br>
<p>Maximum active devices per student. Must be at least 1. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings"                data-endpoint="PUTapi-v1-admin-centers--center-"
               value=""
               data-component="body">
    <br>
<p>Optional settings overrides.</p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-centers--center-">DELETE api/v1/admin/centers/{center}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-centers--center-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/centers/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/architecto"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-centers--center-">
</span>
<span id="execution-results-DELETEapi-v1-admin-centers--center-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-centers--center-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-centers--center-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-centers--center-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-centers--center-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-centers--center-" data-method="DELETE"
      data-path="api/v1/admin/centers/{center}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-centers--center-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-centers--center-"
                    onclick="tryItOut('DELETEapi-v1-admin-centers--center-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-centers--center-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-centers--center-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-centers--center-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/centers/{center}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-centers--center-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-centers--center-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center"                data-endpoint="DELETEapi-v1-admin-centers--center-"
               value="architecto"
               data-component="url">
    <br>
<p>The center. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-centers--center--restore">POST api/v1/admin/centers/{center}/restore</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-centers--center--restore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/centers/architecto/restore" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/architecto/restore"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-centers--center--restore">
</span>
<span id="execution-results-POSTapi-v1-admin-centers--center--restore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-centers--center--restore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-centers--center--restore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-centers--center--restore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-centers--center--restore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-centers--center--restore" data-method="POST"
      data-path="api/v1/admin/centers/{center}/restore"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-centers--center--restore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-centers--center--restore"
                    onclick="tryItOut('POSTapi-v1-admin-centers--center--restore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-centers--center--restore"
                    onclick="cancelTryOut('POSTapi-v1-admin-centers--center--restore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-centers--center--restore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/centers/{center}/restore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-centers--center--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-centers--center--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-centers--center--restore"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center"                data-endpoint="POSTapi-v1-admin-centers--center--restore"
               value="architecto"
               data-component="url">
    <br>
<p>The center. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-enrollments">POST api/v1/admin/enrollments</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-enrollments">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/enrollments" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"user_id\": 10,
    \"course_id\": 5,
    \"status\": \"ACTIVE\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/enrollments"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "user_id": 10,
    "course_id": 5,
    "status": "ACTIVE"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-enrollments">
</span>
<span id="execution-results-POSTapi-v1-admin-enrollments" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-enrollments"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-enrollments"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-enrollments" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-enrollments">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-enrollments" data-method="POST"
      data-path="api/v1/admin/enrollments"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-enrollments', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-enrollments"
                    onclick="tryItOut('POSTapi-v1-admin-enrollments');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-enrollments"
                    onclick="cancelTryOut('POSTapi-v1-admin-enrollments');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-enrollments"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/enrollments</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="10"
               data-component="body">
    <br>
<p>Student user ID to enroll. The <code>id</code> of an existing record in the users table. Example: <code>10</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="5"
               data-component="body">
    <br>
<p>Course to enroll the student in. The <code>id</code> of an existing record in the courses table. Example: <code>5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="POSTapi-v1-admin-enrollments"
               value="ACTIVE"
               data-component="body">
    <br>
<p>Enrollment status (default ACTIVE). Example: <code>ACTIVE</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ACTIVE</code></li> <li><code>DEACTIVATED</code></li> <li><code>CANCELLED</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-v1-admin-enrollments--enrollment_id-">PUT api/v1/admin/enrollments/{enrollment_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-enrollments--enrollment_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/enrollments/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"status\": \"DEACTIVATED\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/enrollments/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "status": "DEACTIVATED"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-enrollments--enrollment_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-enrollments--enrollment_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-enrollments--enrollment_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-enrollments--enrollment_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-enrollments--enrollment_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-enrollments--enrollment_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-enrollments--enrollment_id-" data-method="PUT"
      data-path="api/v1/admin/enrollments/{enrollment_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-enrollments--enrollment_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-enrollments--enrollment_id-"
                    onclick="tryItOut('PUTapi-v1-admin-enrollments--enrollment_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-enrollments--enrollment_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-enrollments--enrollment_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-enrollments--enrollment_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/enrollments/{enrollment_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-enrollments--enrollment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-enrollments--enrollment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-enrollments--enrollment_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>enrollment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="enrollment_id"                data-endpoint="PUTapi-v1-admin-enrollments--enrollment_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the enrollment. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PUTapi-v1-admin-enrollments--enrollment_id-"
               value="DEACTIVATED"
               data-component="body">
    <br>
<p>New enrollment status. Example: <code>DEACTIVATED</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>ACTIVE</code></li> <li><code>DEACTIVATED</code></li> <li><code>CANCELLED</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-enrollments--enrollment_id-">DELETE api/v1/admin/enrollments/{enrollment_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-enrollments--enrollment_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/enrollments/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/enrollments/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-enrollments--enrollment_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-enrollments--enrollment_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-enrollments--enrollment_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-enrollments--enrollment_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-enrollments--enrollment_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-enrollments--enrollment_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-enrollments--enrollment_id-" data-method="DELETE"
      data-path="api/v1/admin/enrollments/{enrollment_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-enrollments--enrollment_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-enrollments--enrollment_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-enrollments--enrollment_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-enrollments--enrollment_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-enrollments--enrollment_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-enrollments--enrollment_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/enrollments/{enrollment_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-enrollments--enrollment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-enrollments--enrollment_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-enrollments--enrollment_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>enrollment_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="enrollment_id"                data-endpoint="DELETEapi-v1-admin-enrollments--enrollment_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the enrollment. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses">GET api/v1/admin/courses</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses?per_page=15&amp;page=1&amp;center_id=2&amp;category_id=3&amp;primary_instructor_id=5&amp;search=Biology" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses"
);

const params = {
    "per_page": "15",
    "page": "1",
    "center_id": "2",
    "category_id": "3",
    "primary_instructor_id": "5",
    "search": "Biology",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses">
    </span>
<span id="execution-results-GETapi-v1-admin-courses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses" data-method="GET"
      data-path="api/v1/admin/courses"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses"
                    onclick="tryItOut('GETapi-v1-admin-courses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses"
                    onclick="cancelTryOut('GETapi-v1-admin-courses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-courses"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-courses"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-courses"
               value="2"
               data-component="query">
    <br>
<p>Filter courses by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="category_id"                data-endpoint="GETapi-v1-admin-courses"
               value="3"
               data-component="query">
    <br>
<p>Filter courses by category ID. Example: <code>3</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>primary_instructor_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="primary_instructor_id"                data-endpoint="GETapi-v1-admin-courses"
               value="5"
               data-component="query">
    <br>
<p>Filter courses by primary instructor ID. Example: <code>5</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-admin-courses"
               value="Biology"
               data-component="query">
    <br>
<p>Search courses by title. Example: <code>Biology</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses">POST api/v1/admin/courses</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"title\": \"Sample Course\",
    \"description\": \"This is an introductory course.\",
    \"category_id\": 1,
    \"center_id\": 1,
    \"difficulty\": \"beginner\",
    \"language\": \"en\",
    \"price\": 0,
    \"metadata\": {
        \"key\": \"value\"
    },
    \"title_translations\": [
        \"Sample Course\"
    ],
    \"description_translations\": [
        \"Intro course\"
    ],
    \"difficulty_level\": 1,
    \"created_by\": 5
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "title": "Sample Course",
    "description": "This is an introductory course.",
    "category_id": 1,
    "center_id": 1,
    "difficulty": "beginner",
    "language": "en",
    "price": 0,
    "metadata": {
        "key": "value"
    },
    "title_translations": [
        "Sample Course"
    ],
    "description_translations": [
        "Intro course"
    ],
    "difficulty_level": 1,
    "created_by": 5
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses">
</span>
<span id="execution-results-POSTapi-v1-admin-courses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses" data-method="POST"
      data-path="api/v1/admin/courses"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses"
                    onclick="tryItOut('POSTapi-v1-admin-courses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-admin-courses"
               value="Sample Course"
               data-component="body">
    <br>
<p>Course title (base locale string). Must not be greater than 255 characters. Example: <code>Sample Course</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-admin-courses"
               value="This is an introductory course."
               data-component="body">
    <br>
<p>Course description (base locale string). Example: <code>This is an introductory course.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="POSTapi-v1-admin-courses"
               value="1"
               data-component="body">
    <br>
<p>Category ID for the course. The <code>id</code> of an existing record in the categories table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center_id"                data-endpoint="POSTapi-v1-admin-courses"
               value="1"
               data-component="body">
    <br>
<p>Center ID offering the course. The <code>id</code> of an existing record in the centers table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="difficulty"                data-endpoint="POSTapi-v1-admin-courses"
               value="beginner"
               data-component="body">
    <br>
<p>Difficulty level slug. Example: <code>beginner</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>beginner</code></li> <li><code>intermediate</code></li> <li><code>advanced</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>language</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="language"                data-endpoint="POSTapi-v1-admin-courses"
               value="en"
               data-component="body">
    <br>
<p>Primary language code. Must not be greater than 10 characters. Example: <code>en</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="POSTapi-v1-admin-courses"
               value="0"
               data-component="body">
    <br>
<p>Optional course price. Must be at least 0. Example: <code>0</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>metadata</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="metadata"                data-endpoint="POSTapi-v1-admin-courses"
               value=""
               data-component="body">
    <br>
<p>Optional metadata array.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title_translations[0]"                data-endpoint="POSTapi-v1-admin-courses"
               data-component="body">
        <input type="text" style="display: none"
               name="title_translations[1]"                data-endpoint="POSTapi-v1-admin-courses"
               data-component="body">
    <br>
<p>Localized title value. Must not be greater than 255 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations[0]"                data-endpoint="POSTapi-v1-admin-courses"
               data-component="body">
        <input type="text" style="display: none"
               name="description_translations[1]"                data-endpoint="POSTapi-v1-admin-courses"
               data-component="body">
    <br>
<p>Localized description value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty_level</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="difficulty_level"                data-endpoint="POSTapi-v1-admin-courses"
               value="1"
               data-component="body">
    <br>
<p>Mapped numeric difficulty (auto-set from difficulty). Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>created_by</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="created_by"                data-endpoint="POSTapi-v1-admin-courses"
               value="5"
               data-component="body">
    <br>
<p>User ID creating the course. The <code>id</code> of an existing record in the users table. Example: <code>5</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id-">GET api/v1/admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id-">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id-" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id-"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id-"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-courses--course_id-">PUT api/v1/admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"title\": \"Updated Course Title\",
    \"description\": \"Updated description.\",
    \"category_id\": 2,
    \"center_id\": 1,
    \"difficulty\": \"intermediate\",
    \"language\": \"en\",
    \"price\": 10.5,
    \"metadata\": {
        \"key\": \"value\"
    },
    \"title_translations\": [
        \"Updated Course\"
    ],
    \"description_translations\": [
        \"Updated desc\"
    ],
    \"difficulty_level\": 2,
    \"created_by\": 5
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "title": "Updated Course Title",
    "description": "Updated description.",
    "category_id": 2,
    "center_id": 1,
    "difficulty": "intermediate",
    "language": "en",
    "price": 10.5,
    "metadata": {
        "key": "value"
    },
    "title_translations": [
        "Updated Course"
    ],
    "description_translations": [
        "Updated desc"
    ],
    "difficulty_level": 2,
    "created_by": 5
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-courses--course_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-courses--course_id-" data-method="PUT"
      data-path="api/v1/admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-courses--course_id-"
                    onclick="tryItOut('PUTapi-v1-admin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-courses--course_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="Updated Course Title"
               data-component="body">
    <br>
<p>Course title (base locale string). Must not be greater than 255 characters. Example: <code>Updated Course Title</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="Updated description."
               data-component="body">
    <br>
<p>Course description (base locale string). Example: <code>Updated description.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>category_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="category_id"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="2"
               data-component="body">
    <br>
<p>Category ID for the course. The <code>id</code> of an existing record in the categories table. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="center_id"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="1"
               data-component="body">
    <br>
<p>Center ID offering the course. The <code>id</code> of an existing record in the centers table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="difficulty"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="intermediate"
               data-component="body">
    <br>
<p>Difficulty level slug. Example: <code>intermediate</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>beginner</code></li> <li><code>intermediate</code></li> <li><code>advanced</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>language</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="language"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="en"
               data-component="body">
    <br>
<p>Primary language code. Must not be greater than 10 characters. Example: <code>en</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>price</code></b>&nbsp;&nbsp;
<small>number</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="price"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="10.5"
               data-component="body">
    <br>
<p>Optional course price. Must be at least 0. Example: <code>10.5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>metadata</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="metadata"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value=""
               data-component="body">
    <br>
<p>Optional metadata array.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title_translations[0]"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               data-component="body">
        <input type="text" style="display: none"
               name="title_translations[1]"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               data-component="body">
    <br>
<p>Localized title value. Must not be greater than 255 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations[0]"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               data-component="body">
        <input type="text" style="display: none"
               name="description_translations[1]"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               data-component="body">
    <br>
<p>Localized description value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty_level</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="difficulty_level"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="2"
               data-component="body">
    <br>
<p>Mapped numeric difficulty (auto-set from difficulty). Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>created_by</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="created_by"                data-endpoint="PUTapi-v1-admin-courses--course_id-"
               value="5"
               data-component="body">
    <br>
<p>User ID updating the course. The <code>id</code> of an existing record in the users table. Example: <code>5</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id-">DELETE api/v1/admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--clone">POST api/v1/admin/courses/{course_id}/clone</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--clone">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/clone" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"options\": {
        \"include_sections\": true,
        \"include_videos\": true,
        \"include_pdfs\": true
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/clone"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "options": {
        "include_sections": true,
        "include_videos": true,
        "include_pdfs": true
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--clone">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--clone" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--clone"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--clone"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--clone" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--clone">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--clone" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/clone"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--clone', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--clone"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--clone');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--clone"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--clone');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--clone"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/clone</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>options</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Clone options.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>include_sections</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_sections"
                   value="true"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_sections"
                   value="false"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether to include sections in the clone. Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>include_videos</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_videos"
                   value="true"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_videos"
                   value="false"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether to include videos in the clone. Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>include_pdfs</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_pdfs"
                   value="true"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-admin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_pdfs"
                   value="false"
                   data-endpoint="POSTapi-v1-admin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether to include PDFs in the clone. Example: <code>false</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--publish">POST api/v1/admin/courses/{course_id}/publish</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/publish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/publish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--publish">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--publish" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/publish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--publish"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--publish"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--publish"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--publish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections">GET api/v1/admin/courses/{course_id}/sections</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course--sections">POST api/v1/admin/courses/{course}/sections</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course--sections">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"course_id\": 1,
    \"title\": \"Introduction\",
    \"description\": \"Overview of the course.\",
    \"sort_order\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "course_id": 1,
    "title": "Introduction",
    "description": "Overview of the course.",
    "sort_order": 1
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course--sections">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course--sections" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course--sections"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course--sections"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course--sections" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course--sections">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course--sections" data-method="POST"
      data-path="api/v1/admin/courses/{course}/sections"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course--sections', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course--sections"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course--sections');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course--sections"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course--sections');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course--sections"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course}/sections</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="1"
               data-component="body">
    <br>
<p>ID of the parent course. The <code>id</code> of an existing record in the courses table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="Introduction"
               data-component="body">
    <br>
<p>Section title (base locale string). Must not be greater than 255 characters. Example: <code>Introduction</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="Overview of the course."
               data-component="body">
    <br>
<p>Section description (base locale string). Example: <code>Overview of the course.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort_order</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sort_order"                data-endpoint="POSTapi-v1-admin-courses--course--sections"
               value="1"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-v1-admin-courses--course_id--sections-reorder">PUT api/v1/admin/courses/{course_id}/sections/reorder</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-courses--course_id--sections-reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/reorder" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"sections\": [
        2
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/reorder"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "sections": [
        2
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-courses--course_id--sections-reorder">
</span>
<span id="execution-results-PUTapi-v1-admin-courses--course_id--sections-reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-courses--course_id--sections-reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-courses--course_id--sections-reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-courses--course_id--sections-reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-courses--course_id--sections-reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-courses--course_id--sections-reorder" data-method="PUT"
      data-path="api/v1/admin/courses/{course_id}/sections/reorder"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-courses--course_id--sections-reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-courses--course_id--sections-reorder"
                    onclick="tryItOut('PUTapi-v1-admin-courses--course_id--sections-reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-courses--course_id--sections-reorder"
                    onclick="cancelTryOut('PUTapi-v1-admin-courses--course_id--sections-reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-courses--course_id--sections-reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sections</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sections[0]"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               data-component="body">
        <input type="number" style="display: none"
               name="sections[1]"                data-endpoint="PUTapi-v1-admin-courses--course_id--sections-reorder"
               data-component="body">
    <br>
<p>Section ID. The <code>id</code> of an existing record in the sections table.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections--section_id-">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections--section_id-" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections--section_id-"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections--section_id-"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-courses--course--sections--section_id-">PUT api/v1/admin/courses/{course}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-courses--course--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"title\": \"Updated Section Title\",
    \"description\": \"Updated description.\",
    \"sort_order\": 2
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "title": "Updated Section Title",
    "description": "Updated description.",
    "sort_order": 2
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-courses--course--sections--section_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-courses--course--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-courses--course--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-courses--course--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-courses--course--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-courses--course--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-courses--course--sections--section_id-" data-method="PUT"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-courses--course--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-courses--course--sections--section_id-"
                    onclick="tryItOut('PUTapi-v1-admin-courses--course--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-courses--course--sections--section_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-courses--course--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-courses--course--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="Updated Section Title"
               data-component="body">
    <br>
<p>Section title (base locale string). Must not be greater than 255 characters. Example: <code>Updated Section Title</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="Updated description."
               data-component="body">
    <br>
<p>Section description (base locale string). Example: <code>Updated description.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort_order</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sort_order"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id-"
               value="2"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>2</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course--sections--section_id-">DELETE api/v1/admin/courses/{course}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course--sections--section_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course--sections--section_id-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course--sections--section_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course--sections--section_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--restore">POST api/v1/admin/courses/{course}/sections/{section_id}/restore</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course--sections--section_id--restore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/restore" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/restore"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course--sections--section_id--restore">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course--sections--section_id--restore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course--sections--section_id--restore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course--sections--section_id--restore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course--sections--section_id--restore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course--sections--section_id--restore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course--sections--section_id--restore" data-method="POST"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}/restore"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course--sections--section_id--restore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course--sections--section_id--restore"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course--sections--section_id--restore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course--sections--section_id--restore"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course--sections--section_id--restore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course--sections--section_id--restore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}/restore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--restore"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--restore"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--restore"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--restore"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">PATCH api/v1/admin/courses/{course_id}/sections/{section_id}/visibility</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/visibility" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/visibility"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "PATCH",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">
</span>
<span id="execution-results-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility" data-method="PATCH"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/visibility"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
                    onclick="tryItOut('PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
                    onclick="cancelTryOut('PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/visibility</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="PATCHapi-v1-admin-courses--course_id--sections--section_id--visibility"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course--sections-structure">POST api/v1/admin/courses/{course}/sections/structure</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course--sections-structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"course_id\": 1,
    \"title\": \"Introduction\",
    \"description\": \"Overview of the course.\",
    \"sort_order\": 1,
    \"videos\": [
        5
    ],
    \"pdfs\": [
        3
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/structure"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "course_id": 1,
    "title": "Introduction",
    "description": "Overview of the course.",
    "sort_order": 1,
    "videos": [
        5
    ],
    "pdfs": [
        3
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course--sections-structure">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course--sections-structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course--sections-structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course--sections-structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course--sections-structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course--sections-structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course--sections-structure" data-method="POST"
      data-path="api/v1/admin/courses/{course}/sections/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course--sections-structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course--sections-structure"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course--sections-structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course--sections-structure"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course--sections-structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course--sections-structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course}/sections/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="1"
               data-component="body">
    <br>
<p>ID of the parent course. The <code>id</code> of an existing record in the courses table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="Introduction"
               data-component="body">
    <br>
<p>Section title (base locale string). Must not be greater than 255 characters. Example: <code>Introduction</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="Overview of the course."
               data-component="body">
    <br>
<p>Section description (base locale string). Example: <code>Overview of the course.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort_order</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sort_order"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               value="1"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>videos</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="videos[0]"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               data-component="body">
        <input type="number" style="display: none"
               name="videos[1]"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               data-component="body">
    <br>
<p>Video ID to attach. The <code>id</code> of an existing record in the videos table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdfs</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdfs[0]"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               data-component="body">
        <input type="number" style="display: none"
               name="pdfs[1]"                data-endpoint="POSTapi-v1-admin-courses--course--sections-structure"
               data-component="body">
    <br>
<p>PDF ID to attach. The <code>id</code> of an existing record in the pdfs table.</p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-v1-admin-courses--course--sections--section_id--structure">PUT api/v1/admin/courses/{course}/sections/{section_id}/structure</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-courses--course--sections--section_id--structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"title\": \"Updated Section\",
    \"description\": \"Updated description.\",
    \"sort_order\": 2,
    \"videos\": [
        5
    ],
    \"pdfs\": [
        3
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/structure"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "title": "Updated Section",
    "description": "Updated description.",
    "sort_order": 2,
    "videos": [
        5
    ],
    "pdfs": [
        3
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-courses--course--sections--section_id--structure">
</span>
<span id="execution-results-PUTapi-v1-admin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-courses--course--sections--section_id--structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-courses--course--sections--section_id--structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-courses--course--sections--section_id--structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-courses--course--sections--section_id--structure" data-method="PUT"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-courses--course--sections--section_id--structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-courses--course--sections--section_id--structure"
                    onclick="tryItOut('PUTapi-v1-admin-courses--course--sections--section_id--structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-courses--course--sections--section_id--structure"
                    onclick="cancelTryOut('PUTapi-v1-admin-courses--course--sections--section_id--structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-courses--course--sections--section_id--structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="Updated Section"
               data-component="body">
    <br>
<p>Section title (base locale string). Must not be greater than 255 characters. Example: <code>Updated Section</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="Updated description."
               data-component="body">
    <br>
<p>Section description (base locale string). Example: <code>Updated description.</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort_order</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="sort_order"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               value="2"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>videos</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="videos[0]"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               data-component="body">
        <input type="number" style="display: none"
               name="videos[1]"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               data-component="body">
    <br>
<p>Video ID to attach. The <code>id</code> of an existing record in the videos table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdfs</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdfs[0]"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               data-component="body">
        <input type="number" style="display: none"
               name="pdfs[1]"                data-endpoint="PUTapi-v1-admin-courses--course--sections--section_id--structure"
               data-component="body">
    <br>
<p>PDF ID to attach. The <code>id</code> of an existing record in the pdfs table.</p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course--sections--section_id--structure">DELETE api/v1/admin/courses/{course}/sections/{section_id}/structure</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course--sections--section_id--structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/structure"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course--sections--section_id--structure">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course--sections--section_id--structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course--sections--section_id--structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course--sections--section_id--structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course--sections--section_id--structure" data-method="DELETE"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course--sections--section_id--structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course--sections--section_id--structure"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course--sections--section_id--structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course--sections--section_id--structure"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course--sections--section_id--structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course--sections--section_id--structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id--structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id--structure"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id--structure"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id--structure"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="DELETEapi-v1-admin-courses--course--sections--section_id--structure"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos">GET api/v1/admin/courses/{course_id}/sections/{section_id}/videos</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections--section_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections--section_id--videos">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections--section_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections--section_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections--section_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections--section_id--videos" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">POST api/v1/admin/courses/{course_id}/sections/{section_id}/videos</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"video_id\": 10
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "video_id": 10
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--sections--section_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--sections--section_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--sections--section_id--videos" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--sections--section_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--sections--section_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--sections--section_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--videos"
               value="10"
               data-component="body">
    <br>
<p>Video ID to attach to the section. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">DELETE api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"video_id\": 10
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/videos/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "video_id": 10
};

fetch(url, {
    method: "DELETE",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--videos--video_id-"
               value="10"
               data-component="body">
    <br>
<p>Video ID to detach from the section. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">GET api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">GET api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
    </span>
<span id="execution-results-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" data-method="GET"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="tryItOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="GETapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">POST api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"pdf_id\": 7
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "pdf_id": 7
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--sections--section_id--pdfs"
               value="7"
               data-component="body">
    <br>
<p>PDF ID to attach to the section. The <code>id</code> of an existing record in the pdfs table. Example: <code>7</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">DELETE api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"pdf_id\": 7
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/pdfs/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "pdf_id": 7
};

fetch(url, {
    method: "DELETE",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="7"
               data-component="body">
    <br>
<p>PDF ID to detach from the section. The <code>id</code> of an existing record in the pdfs table. Example: <code>7</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--publish">POST api/v1/admin/courses/{course}/sections/{section_id}/publish</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course--sections--section_id--publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/publish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/publish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course--sections--section_id--publish">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course--sections--section_id--publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course--sections--section_id--publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course--sections--section_id--publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course--sections--section_id--publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course--sections--section_id--publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course--sections--section_id--publish" data-method="POST"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}/publish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course--sections--section_id--publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course--sections--section_id--publish"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course--sections--section_id--publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course--sections--section_id--publish"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course--sections--section_id--publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course--sections--section_id--publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--publish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--publish"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--publish"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--publish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">POST api/v1/admin/courses/{course}/sections/{section_id}/unpublish</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/unpublish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/sections/1/unpublish"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course--sections--section_id--unpublish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course--sections--section_id--unpublish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course--sections--section_id--unpublish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course--sections--section_id--unpublish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course--sections--section_id--unpublish" data-method="POST"
      data-path="api/v1/admin/courses/{course}/sections/{section_id}/unpublish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course--sections--section_id--unpublish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course--sections--section_id--unpublish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course--sections--section_id--unpublish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course}/sections/{section_id}/unpublish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
               value="1"
               data-component="url">
    <br>
<p>The course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-courses--course--sections--section_id--unpublish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETapi-v1-admin-videos">GET api/v1/admin/videos</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/videos?per_page=15&amp;page=1&amp;center_id=2&amp;course_id=10&amp;search=Intro" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/videos"
);

const params = {
    "per_page": "15",
    "page": "1",
    "center_id": "2",
    "course_id": "10",
    "search": "Intro",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-videos">
    </span>
<span id="execution-results-GETapi-v1-admin-videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-videos" data-method="GET"
      data-path="api/v1/admin/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-videos"
                    onclick="tryItOut('GETapi-v1-admin-videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-videos"
                    onclick="cancelTryOut('GETapi-v1-admin-videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-videos"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-videos"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-videos"
               value="2"
               data-component="query">
    <br>
<p>Filter videos by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-videos"
               value="10"
               data-component="query">
    <br>
<p>Filter videos by course ID. Example: <code>10</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-admin-videos"
               value="Intro"
               data-component="query">
    <br>
<p>Search videos by title. Example: <code>Intro</code></p>
            </div>
                </form>

                    <h2 id="endpoints-GETapi-v1-admin-video-upload-sessions">GET api/v1/admin/video-upload-sessions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-video-upload-sessions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/video-upload-sessions?per_page=15&amp;page=1&amp;status=3&amp;center_id=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/video-upload-sessions"
);

const params = {
    "per_page": "15",
    "page": "1",
    "status": "3",
    "center_id": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-video-upload-sessions">
    </span>
<span id="execution-results-GETapi-v1-admin-video-upload-sessions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-video-upload-sessions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-video-upload-sessions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-video-upload-sessions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-video-upload-sessions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-video-upload-sessions" data-method="GET"
      data-path="api/v1/admin/video-upload-sessions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-video-upload-sessions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-video-upload-sessions"
                    onclick="tryItOut('GETapi-v1-admin-video-upload-sessions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-video-upload-sessions"
                    onclick="cancelTryOut('GETapi-v1-admin-video-upload-sessions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-video-upload-sessions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/video-upload-sessions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="3"
               data-component="query">
    <br>
<p>Filter by upload status (0-4). Example: <code>3</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-video-upload-sessions"
               value="1"
               data-component="query">
    <br>
<p>Filter by center ID (admins scoped automatically). Example: <code>1</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--videos">POST api/v1/admin/courses/{course_id}/videos</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"video_id\": 10,
    \"order_index\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/videos"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "video_id": 10,
    "order_index": 1
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--videos">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--videos" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--videos"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--videos"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="10"
               data-component="body">
    <br>
<p>Video ID to attach to the course. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order_index</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="order_index"                data-endpoint="POSTapi-v1-admin-courses--course_id--videos"
               value="1"
               data-component="body">
    <br>
<p>Optional position in the course. Must be at least 0. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id--videos--video-">DELETE api/v1/admin/courses/{course_id}/videos/{video}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id--videos--video-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/videos/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id--videos--video-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id--videos--video-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id--videos--video-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id--videos--video-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id--videos--video-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id--videos--video-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id--videos--video-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}/videos/{video}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id--videos--video-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id--videos--video-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id--videos--video-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id--videos--video-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id--videos--video-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id--videos--video-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}/videos/{video}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id--videos--video-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id--videos--video-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id--videos--video-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--videos--video-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>video</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video"                data-endpoint="DELETEapi-v1-admin-courses--course_id--videos--video-"
               value="1"
               data-component="url">
    <br>
<p>The video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-video-uploads">POST api/v1/admin/video-uploads</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-video-uploads">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/video-uploads" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"center_id\": 1,
    \"video_id\": 10,
    \"original_filename\": \"lecture-1.mp4\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/video-uploads"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "center_id": 1,
    "video_id": 10,
    "original_filename": "lecture-1.mp4"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-video-uploads">
</span>
<span id="execution-results-POSTapi-v1-admin-video-uploads" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-video-uploads"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-video-uploads"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-video-uploads" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-video-uploads">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-video-uploads" data-method="POST"
      data-path="api/v1/admin/video-uploads"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-video-uploads', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-video-uploads"
                    onclick="tryItOut('POSTapi-v1-admin-video-uploads');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-video-uploads"
                    onclick="cancelTryOut('POSTapi-v1-admin-video-uploads');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-video-uploads"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/video-uploads</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="1"
               data-component="body">
    <br>
<p>Center ID to associate the upload with. The <code>id</code> of an existing record in the centers table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="10"
               data-component="body">
    <br>
<p>Optional existing video to attach this upload session to. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>original_filename</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="original_filename"                data-endpoint="POSTapi-v1-admin-video-uploads"
               value="lecture-1.mp4"
               data-component="body">
    <br>
<p>Original filename of the uploaded video. Must not be greater than 255 characters. Example: <code>lecture-1.mp4</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">PATCH api/v1/admin/video-uploads/{videoUploadSession_id}</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://xyz-lms.test/api/v1/admin/video-uploads/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"status\": \"READY\",
    \"progress_percent\": 75,
    \"source_id\": \"bunny-video-id\",
    \"source_url\": \"https:\\/\\/example.com\\/video.mp4\",
    \"duration_seconds\": 180,
    \"error_message\": \"Transcode failed due to invalid codec\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/video-uploads/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "status": "READY",
    "progress_percent": 75,
    "source_id": "bunny-video-id",
    "source_url": "https:\/\/example.com\/video.mp4",
    "duration_seconds": 180,
    "error_message": "Transcode failed due to invalid codec"
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">
</span>
<span id="execution-results-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-" data-method="PATCH"
      data-path="api/v1/admin/video-uploads/{videoUploadSession_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-admin-video-uploads--videoUploadSession_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
                    onclick="tryItOut('PATCHapi-v1-admin-video-uploads--videoUploadSession_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
                    onclick="cancelTryOut('PATCHapi-v1-admin-video-uploads--videoUploadSession_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/admin/video-uploads/{videoUploadSession_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>videoUploadSession_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="videoUploadSession_id"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the videoUploadSession. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="READY"
               data-component="body">
    <br>
<p>New upload status (PENDING, UPLOADING, PROCESSING, READY, FAILED). Example: <code>READY</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>PENDING</code></li> <li><code>UPLOADING</code></li> <li><code>PROCESSING</code></li> <li><code>READY</code></li> <li><code>FAILED</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>progress_percent</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="progress_percent"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="75"
               data-component="body">
    <br>
<p>Optional progress indicator between 0 and 100. Must be at least 0. Must not be greater than 100. Example: <code>75</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>source_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="source_id"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="bunny-video-id"
               data-component="body">
    <br>
<p>Optional Bunny video identifier when READY. Must not be greater than 255 characters. Example: <code>bunny-video-id</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>source_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="source_url"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="https://example.com/video.mp4"
               data-component="body">
    <br>
<p>Optional playback/source URL when READY. Must not be greater than 2048 characters. Example: <code>https://example.com/video.mp4</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>duration_seconds</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="duration_seconds"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="180"
               data-component="body">
    <br>
<p>Optional duration in seconds when READY. Must be at least 1. Example: <code>180</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>error_message</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="error_message"                data-endpoint="PATCHapi-v1-admin-video-uploads--videoUploadSession_id-"
               value="Transcode failed due to invalid codec"
               data-component="body">
    <br>
<p>Optional error details when FAILED. Must not be greater than 2000 characters. Example: <code>Transcode failed due to invalid codec</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-instructors">GET api/v1/admin/instructors</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/instructors?per_page=15&amp;page=1&amp;center_id=2&amp;course_id=10&amp;search=Sara" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/instructors"
);

const params = {
    "per_page": "15",
    "page": "1",
    "center_id": "2",
    "course_id": "10",
    "search": "Sara",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-instructors">
    </span>
<span id="execution-results-GETapi-v1-admin-instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-instructors" data-method="GET"
      data-path="api/v1/admin/instructors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-instructors"
                    onclick="tryItOut('GETapi-v1-admin-instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-instructors"
                    onclick="cancelTryOut('GETapi-v1-admin-instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-instructors"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-instructors"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-instructors"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-instructors"
               value="2"
               data-component="query">
    <br>
<p>Filter instructors by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-instructors"
               value="10"
               data-component="query">
    <br>
<p>Filter instructors by course ID. Example: <code>10</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-admin-instructors"
               value="Sara"
               data-component="query">
    <br>
<p>Search instructors by name. Example: <code>Sara</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-instructors">POST api/v1/admin/instructors</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/instructors" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --form "center_id=1"\
    --form "name_translations[en]=John Doe"\
    --form "name_translations[ar]=جون دو"\
    --form "bio_translations[en]=Senior instructor"\
    --form "bio_translations[ar]=مدرب كبير"\
    --form "title_translations[en]=Professor"\
    --form "title_translations[ar]=أستاذ"\
    --form "avatar_url=https://example.com/avatar.jpg"\
    --form "email=john.doe@example.com"\
    --form "phone=+1234567890"\
    --form "social_links[]=https://linkedin.com/in/johndoe"\
    --form "metadata[specialization]=Math"\
    --form "metadata[languages][]=en"\
    --form "avatar=@/tmp/phpt4lsunnrro73c6pE6uF" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/instructors"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
    "X-Locale": "en",
};

const body = new FormData();
body.append('center_id', '1');
body.append('name_translations[en]', 'John Doe');
body.append('name_translations[ar]', 'جون دو');
body.append('bio_translations[en]', 'Senior instructor');
body.append('bio_translations[ar]', 'مدرب كبير');
body.append('title_translations[en]', 'Professor');
body.append('title_translations[ar]', 'أستاذ');
body.append('avatar_url', 'https://example.com/avatar.jpg');
body.append('email', 'john.doe@example.com');
body.append('phone', '+1234567890');
body.append('social_links[]', 'https://linkedin.com/in/johndoe');
body.append('metadata[specialization]', 'Math');
body.append('metadata[languages][]', 'en');
body.append('avatar', document.querySelector('input[name="avatar"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-instructors">
</span>
<span id="execution-results-POSTapi-v1-admin-instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-instructors" data-method="POST"
      data-path="api/v1/admin/instructors"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-instructors"
                    onclick="tryItOut('POSTapi-v1-admin-instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-instructors"
                    onclick="cancelTryOut('POSTapi-v1-admin-instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-instructors"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-instructors"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="POSTapi-v1-admin-instructors"
               value="1"
               data-component="body">
    <br>
<p>Optional center ID that the instructor belongs to. The <code>id</code> of an existing record in the centers table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name_translations"                data-endpoint="POSTapi-v1-admin-instructors"
               value=""
               data-component="body">
    <br>
<p>Localized instructor name keyed by locale.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bio_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bio_translations"                data-endpoint="POSTapi-v1-admin-instructors"
               value=""
               data-component="body">
    <br>
<p>Localized biography.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title_translations"                data-endpoint="POSTapi-v1-admin-instructors"
               value=""
               data-component="body">
    <br>
<p>Localized title or position.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="avatar_url"                data-endpoint="POSTapi-v1-admin-instructors"
               value="https://example.com/avatar.jpg"
               data-component="body">
    <br>
<p>Profile image URL. Example: <code>https://example.com/avatar.jpg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="avatar"                data-endpoint="POSTapi-v1-admin-instructors"
               value=""
               data-component="body">
    <br>
<p>Profile image file upload. Must be a file. Must be an image. Must not be greater than 512000 kilobytes. Example: <code>/tmp/phpt4lsunnrro73c6pE6uF</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-instructors"
               value="john.doe@example.com"
               data-component="body">
    <br>
<p>Contact email for the instructor. Must be a valid email address. Example: <code>john.doe@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-admin-instructors"
               value="+1234567890"
               data-component="body">
    <br>
<p>Contact phone number. Example: <code>+1234567890</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>social_links</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social_links[0]"                data-endpoint="POSTapi-v1-admin-instructors"
               data-component="body">
        <input type="text" style="display: none"
               name="social_links[1]"                data-endpoint="POSTapi-v1-admin-instructors"
               data-component="body">
    <br>
<p>Social link value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>metadata</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="metadata"                data-endpoint="POSTapi-v1-admin-instructors"
               value=""
               data-component="body">
    <br>
<p>Optional instructor metadata.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-instructors--id-">GET api/v1/admin/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-instructors--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/instructors/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-instructors--id-">
    </span>
<span id="execution-results-GETapi-v1-admin-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-instructors--id-" data-method="GET"
      data-path="api/v1/admin/instructors/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-instructors--id-"
                    onclick="tryItOut('GETapi-v1-admin-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-instructors--id-"
                    onclick="cancelTryOut('GETapi-v1-admin-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-instructors--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-instructors--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-instructors--id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETapi-v1-admin-instructors--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-instructors--id-">PUT api/v1/admin/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-instructors--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/instructors/1" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --form "center_id=1"\
    --form "name_translations[en]=John Doe"\
    --form "name_translations[ar]=جون دو"\
    --form "bio_translations[en]=Senior instructor"\
    --form "bio_translations[ar]=مدرب كبير"\
    --form "title_translations[en]=Professor"\
    --form "title_translations[ar]=أستاذ"\
    --form "avatar_url=https://example.com/avatar.jpg"\
    --form "email=john.doe@example.com"\
    --form "phone=+1234567890"\
    --form "social_links[]=https://linkedin.com/in/johndoe"\
    --form "metadata[specialization]=Physics"\
    --form "avatar=@/tmp/phpba35debngb4edqfsuJq" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/instructors/1"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
    "X-Locale": "en",
};

const body = new FormData();
body.append('center_id', '1');
body.append('name_translations[en]', 'John Doe');
body.append('name_translations[ar]', 'جون دو');
body.append('bio_translations[en]', 'Senior instructor');
body.append('bio_translations[ar]', 'مدرب كبير');
body.append('title_translations[en]', 'Professor');
body.append('title_translations[ar]', 'أستاذ');
body.append('avatar_url', 'https://example.com/avatar.jpg');
body.append('email', 'john.doe@example.com');
body.append('phone', '+1234567890');
body.append('social_links[]', 'https://linkedin.com/in/johndoe');
body.append('metadata[specialization]', 'Physics');
body.append('avatar', document.querySelector('input[name="avatar"]').files[0]);

fetch(url, {
    method: "PUT",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-instructors--id-">
</span>
<span id="execution-results-PUTapi-v1-admin-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-instructors--id-" data-method="PUT"
      data-path="api/v1/admin/instructors/{id}"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-instructors--id-"
                    onclick="tryItOut('PUTapi-v1-admin-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-instructors--id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/instructors/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/admin/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="1"
               data-component="body">
    <br>
<p>Optional center ID for the instructor. The <code>id</code> of an existing record in the centers table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name_translations"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value=""
               data-component="body">
    <br>
<p>Localized instructor name keyed by locale.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>bio_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="bio_translations"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value=""
               data-component="body">
    <br>
<p>Localized biography.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title_translations"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value=""
               data-component="body">
    <br>
<p>Localized title or position.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="avatar_url"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="https://example.com/avatar.jpg"
               data-component="body">
    <br>
<p>Profile image URL. Example: <code>https://example.com/avatar.jpg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>avatar</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="avatar"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value=""
               data-component="body">
    <br>
<p>Profile image file upload. Must be a file. Must be an image. Must not be greater than 512000 kilobytes. Example: <code>/tmp/phpba35debngb4edqfsuJq</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="john.doe@example.com"
               data-component="body">
    <br>
<p>Contact email for the instructor. Must be a valid email address. Example: <code>john.doe@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value="+1234567890"
               data-component="body">
    <br>
<p>Contact phone number. Example: <code>+1234567890</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>social_links</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="social_links[0]"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="social_links[1]"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               data-component="body">
    <br>
<p>Social link value.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>metadata</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="metadata"                data-endpoint="PUTapi-v1-admin-instructors--id-"
               value=""
               data-component="body">
    <br>
<p>Optional instructor metadata.</p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-instructors--id-">DELETE api/v1/admin/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-instructors--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/instructors/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-instructors--id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-instructors--id-" data-method="DELETE"
      data-path="api/v1/admin/instructors/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-instructors--id-"
                    onclick="tryItOut('DELETEapi-v1-admin-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-instructors--id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-instructors--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-instructors--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-instructors--id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="DELETEapi-v1-admin-instructors--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--instructors">POST api/v1/admin/courses/{course_id}/instructors</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/instructors" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"instructor_id\": 5,
    \"role\": \"assistant\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/instructors"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "instructor_id": 5,
    "role": "assistant"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--instructors">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--instructors" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/instructors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--instructors"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--instructors"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>instructor_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="instructor_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="5"
               data-component="body">
    <br>
<p>Instructor ID to assign to the course. The <code>id</code> of an existing record in the instructors table. Example: <code>5</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>role</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="role"                data-endpoint="POSTapi-v1-admin-courses--course_id--instructors"
               value="assistant"
               data-component="body">
    <br>
<p>Optional role for this instructor within the course. Example: <code>assistant</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">DELETE api/v1/admin/courses/{course_id}/instructors/{instructor_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/instructors/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}/instructors/{instructor_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}/instructors/{instructor_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>instructor_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="instructor_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--instructors--instructor_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-courses--course_id--pdfs">POST api/v1/admin/courses/{course_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-courses--course_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/courses/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"pdf_id\": 12,
    \"order_index\": 2
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/pdfs"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "pdf_id": 12,
    "order_index": 2
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-courses--course_id--pdfs">
</span>
<span id="execution-results-POSTapi-v1-admin-courses--course_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-courses--course_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-courses--course_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-courses--course_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-courses--course_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-courses--course_id--pdfs" data-method="POST"
      data-path="api/v1/admin/courses/{course_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-courses--course_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-courses--course_id--pdfs"
                    onclick="tryItOut('POSTapi-v1-admin-courses--course_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-courses--course_id--pdfs"
                    onclick="cancelTryOut('POSTapi-v1-admin-courses--course_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-courses--course_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/courses/{course_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdf_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf_id"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="12"
               data-component="body">
    <br>
<p>PDF ID to attach to the course. The <code>id</code> of an existing record in the pdfs table. Example: <code>12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order_index</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="order_index"                data-endpoint="POSTapi-v1-admin-courses--course_id--pdfs"
               value="2"
               data-component="body">
    <br>
<p>Optional position in the course. Must be at least 0. Example: <code>2</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">DELETE api/v1/admin/courses/{course_id}/pdfs/{pdf}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/courses/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/courses/1/pdfs/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">
</span>
<span id="execution-results-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-" data-method="DELETE"
      data-path="api/v1/admin/courses/{course_id}/pdfs/{pdf}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-courses--course_id--pdfs--pdf-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
                    onclick="tryItOut('DELETEapi-v1-admin-courses--course_id--pdfs--pdf-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-courses--course_id--pdfs--pdf-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/courses/{course_id}/pdfs/{pdf}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>pdf</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdf"                data-endpoint="DELETEapi-v1-admin-courses--course_id--pdfs--pdf-"
               value="1"
               data-component="url">
    <br>
<p>The pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-pdfs">POST api/v1/admin/pdfs</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/pdfs" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --form "title_translations[en]=Lesson Notes"\
    --form "title_translations[ar]=ملاحظات الدرس"\
    --form "description_translations[en]=Downloadable PDF for lesson 1"\
    --form "course_id=1"\
    --form "section_id=2"\
    --form "video_id=3"\
    --form "file=@/tmp/php0ros175bi0t8cu6mV6P" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/pdfs"
);

const headers = {
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
    "X-Locale": "en",
};

const body = new FormData();
body.append('title_translations[en]', 'Lesson Notes');
body.append('title_translations[ar]', 'ملاحظات الدرس');
body.append('description_translations[en]', 'Downloadable PDF for lesson 1');
body.append('course_id', '1');
body.append('section_id', '2');
body.append('video_id', '3');
body.append('file', document.querySelector('input[name="file"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-pdfs">
</span>
<span id="execution-results-POSTapi-v1-admin-pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-pdfs" data-method="POST"
      data-path="api/v1/admin/pdfs"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-pdfs"
                    onclick="tryItOut('POSTapi-v1-admin-pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-pdfs"
                    onclick="cancelTryOut('POSTapi-v1-admin-pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>title_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="title_translations"                data-endpoint="POSTapi-v1-admin-pdfs"
               value=""
               data-component="body">
    <br>
<p>Localized title for the PDF.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations"                data-endpoint="POSTapi-v1-admin-pdfs"
               value=""
               data-component="body">
    <br>
<p>Optional localized description.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>file</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="file"                data-endpoint="POSTapi-v1-admin-pdfs"
               value=""
               data-component="body">
    <br>
<p>PDF file to upload (max 50MB). Must be a file. Must not be greater than 51200 kilobytes. Example: <code>/tmp/php0ros175bi0t8cu6mV6P</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="1"
               data-component="body">
    <br>
<p>Optional course to attach the PDF to. The <code>id</code> of an existing record in the courses table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>section_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="section_id"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="2"
               data-component="body">
    <br>
<p>Optional section to attach the PDF to (must belong to the course). The <code>id</code> of an existing record in the sections table. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="POSTapi-v1-admin-pdfs"
               value="3"
               data-component="body">
    <br>
<p>Optional video association (must belong to the course). The <code>id</code> of an existing record in the videos table. Example: <code>3</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-centers--center_id--settings">GET api/v1/admin/centers/{center_id}/settings</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-centers--center_id--settings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/centers/1/settings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/1/settings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-centers--center_id--settings">
    </span>
<span id="execution-results-GETapi-v1-admin-centers--center_id--settings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-centers--center_id--settings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-centers--center_id--settings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-centers--center_id--settings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-centers--center_id--settings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-centers--center_id--settings" data-method="GET"
      data-path="api/v1/admin/centers/{center_id}/settings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-centers--center_id--settings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-centers--center_id--settings"
                    onclick="tryItOut('GETapi-v1-admin-centers--center_id--settings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-centers--center_id--settings"
                    onclick="cancelTryOut('GETapi-v1-admin-centers--center_id--settings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-centers--center_id--settings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/centers/{center_id}/settings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-centers--center_id--settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-centers--center_id--settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-centers--center_id--settings"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-centers--center_id--settings"
               value="1"
               data-component="url">
    <br>
<p>The ID of the center. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PATCHapi-v1-admin-centers--center_id--settings">PATCH api/v1/admin/centers/{center_id}/settings</h2>

<p>
</p>



<span id="example-requests-PATCHapi-v1-admin-centers--center_id--settings">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://xyz-lms.test/api/v1/admin/centers/1/settings" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"settings\": {
        \"default_view_limit\": 3,
        \"allow_extra_view_requests\": true,
        \"pdf_download_permission\": true,
        \"device_limit\": 1,
        \"branding\": {
            \"logo_url\": \"https:\\/\\/example.com\\/logo.png\",
            \"primary_color\": \"#000000\"
        }
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/centers/1/settings"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "settings": {
        "default_view_limit": 3,
        "allow_extra_view_requests": true,
        "pdf_download_permission": true,
        "device_limit": 1,
        "branding": {
            "logo_url": "https:\/\/example.com\/logo.png",
            "primary_color": "#000000"
        }
    }
};

fetch(url, {
    method: "PATCH",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PATCHapi-v1-admin-centers--center_id--settings">
</span>
<span id="execution-results-PATCHapi-v1-admin-centers--center_id--settings" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHapi-v1-admin-centers--center_id--settings"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHapi-v1-admin-centers--center_id--settings"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHapi-v1-admin-centers--center_id--settings" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHapi-v1-admin-centers--center_id--settings">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHapi-v1-admin-centers--center_id--settings" data-method="PATCH"
      data-path="api/v1/admin/centers/{center_id}/settings"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHapi-v1-admin-centers--center_id--settings', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHapi-v1-admin-centers--center_id--settings"
                    onclick="tryItOut('PATCHapi-v1-admin-centers--center_id--settings');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHapi-v1-admin-centers--center_id--settings"
                    onclick="cancelTryOut('PATCHapi-v1-admin-centers--center_id--settings');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHapi-v1-admin-centers--center_id--settings"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/admin/centers/{center_id}/settings</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="1"
               data-component="url">
    <br>
<p>The ID of the center. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>settings</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Center settings payload.</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>default_view_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="settings.default_view_limit"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="3"
               data-component="body">
    <br>
<p>Default view limit for videos. Must be at least 0. Example: <code>3</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>allow_extra_view_requests</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-admin-centers--center_id--settings" style="display: none">
            <input type="radio" name="settings.allow_extra_view_requests"
                   value="true"
                   data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-admin-centers--center_id--settings" style="display: none">
            <input type="radio" name="settings.allow_extra_view_requests"
                   value="false"
                   data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether students can request extra views. Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>pdf_download_permission</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="PATCHapi-v1-admin-centers--center_id--settings" style="display: none">
            <input type="radio" name="settings.pdf_download_permission"
                   value="true"
                   data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="PATCHapi-v1-admin-centers--center_id--settings" style="display: none">
            <input type="radio" name="settings.pdf_download_permission"
                   value="false"
                   data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Whether PDF downloads are allowed. Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>device_limit</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="settings.device_limit"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="1"
               data-component="body">
    <br>
<p>Maximum active devices per student. Must be at least 1. Example: <code>1</code></p>
                    </div>
                                                                <div style=" margin-left: 14px; clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>branding</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Branding settings payload.</p>
            </summary>
                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>logo_url</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings.branding.logo_url"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="https://example.com/logo.png"
               data-component="body">
    <br>
<p>Logo URL. Example: <code>https://example.com/logo.png</code></p>
                    </div>
                                                                <div style="margin-left: 28px; clear: unset;">
                        <b style="line-height: 2;"><code>primary_color</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="settings.branding.primary_color"                data-endpoint="PATCHapi-v1-admin-centers--center_id--settings"
               value="#000000"
               data-component="body">
    <br>
<p>Primary branding color. Example: <code>#000000</code></p>
                    </div>
                                    </details>
        </div>
                                        </details>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-settings-preview">GET api/v1/admin/settings/preview</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-settings-preview">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/settings/preview" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"student_id\": 1,
    \"video_id\": 2,
    \"course_id\": 3,
    \"center_id\": 4
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/settings/preview"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "student_id": 1,
    "video_id": 2,
    "course_id": 3,
    "center_id": 4
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-settings-preview">
    </span>
<span id="execution-results-GETapi-v1-admin-settings-preview" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-settings-preview"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-settings-preview"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-settings-preview" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-settings-preview">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-settings-preview" data-method="GET"
      data-path="api/v1/admin/settings/preview"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-settings-preview', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-settings-preview"
                    onclick="tryItOut('GETapi-v1-admin-settings-preview');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-settings-preview"
                    onclick="cancelTryOut('GETapi-v1-admin-settings-preview');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-settings-preview"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/settings/preview</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>student_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="student_id"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="1"
               data-component="body">
    <br>
<p>Optional student ID for resolution context. The <code>id</code> of an existing record in the users table. Example: <code>1</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>video_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="video_id"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="2"
               data-component="body">
    <br>
<p>Optional video ID for resolution context. The <code>id</code> of an existing record in the videos table. Example: <code>2</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>course_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="course_id"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="3"
               data-component="body">
    <br>
<p>Optional course ID for resolution context. The <code>id</code> of an existing record in the courses table. Example: <code>3</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-settings-preview"
               value="4"
               data-component="body">
    <br>
<p>Optional center ID for resolution context. The <code>id</code> of an existing record in the centers table. Example: <code>4</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-audit-logs">GET api/v1/admin/audit-logs</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-audit-logs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/audit-logs?center_id=2&amp;entity_type=App%5CModels%5CCourse&amp;entity_id=12&amp;action=enrollment_created&amp;user_id=3&amp;date_from=2025-01-01&amp;date_to=2025-12-31&amp;per_page=20&amp;page=1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/audit-logs"
);

const params = {
    "center_id": "2",
    "entity_type": "App\Models\Course",
    "entity_id": "12",
    "action": "enrollment_created",
    "user_id": "3",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
    "per_page": "20",
    "page": "1",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-audit-logs">
    </span>
<span id="execution-results-GETapi-v1-admin-audit-logs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-audit-logs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-audit-logs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-audit-logs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-audit-logs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-audit-logs" data-method="GET"
      data-path="api/v1/admin/audit-logs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-audit-logs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-audit-logs"
                    onclick="tryItOut('GETapi-v1-admin-audit-logs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-audit-logs"
                    onclick="cancelTryOut('GETapi-v1-admin-audit-logs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-audit-logs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/audit-logs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="2"
               data-component="query">
    <br>
<p>Filter by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>entity_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="entity_type"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="App\Models\Course"
               data-component="query">
    <br>
<p>Filter by entity class/type. Must not be greater than 255 characters. Example: <code>App\Models\Course</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>entity_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="entity_id"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="12"
               data-component="query">
    <br>
<p>Filter by specific entity id. Example: <code>12</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>action</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="action"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="enrollment_created"
               data-component="query">
    <br>
<p>Filter by audit action. Must not be greater than 255 characters. Example: <code>enrollment_created</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="3"
               data-component="query">
    <br>
<p>Filter by actor user id. Example: <code>3</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_from"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="2025-01-01"
               data-component="query">
    <br>
<p>Filter logs starting from this date. Must be a valid date. Example: <code>2025-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_to"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="2025-12-31"
               data-component="query">
    <br>
<p>Filter logs up to this date. Must be a valid date. Example: <code>2025-12-31</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="20"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-audit-logs"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                </form>

                    <h2 id="endpoints-GETapi-v1-admin-extra-view-requests">GET api/v1/admin/extra-view-requests</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-extra-view-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/extra-view-requests?per_page=15&amp;page=1&amp;status=PENDING&amp;center_id=2&amp;user_id=5&amp;date_from=2025-01-01&amp;date_to=2025-12-31" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/extra-view-requests"
);

const params = {
    "per_page": "15",
    "page": "1",
    "status": "PENDING",
    "center_id": "2",
    "user_id": "5",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-extra-view-requests">
    </span>
<span id="execution-results-GETapi-v1-admin-extra-view-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-extra-view-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-extra-view-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-extra-view-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-extra-view-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-extra-view-requests" data-method="GET"
      data-path="api/v1/admin/extra-view-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-extra-view-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-extra-view-requests"
                    onclick="tryItOut('GETapi-v1-admin-extra-view-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-extra-view-requests"
                    onclick="cancelTryOut('GETapi-v1-admin-extra-view-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-extra-view-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/extra-view-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="PENDING"
               data-component="query">
    <br>
<p>Filter by request status. Example: <code>PENDING</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>PENDING</code></li> <li><code>APPROVED</code></li> <li><code>REJECTED</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="2"
               data-component="query">
    <br>
<p>Filter by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="5"
               data-component="query">
    <br>
<p>Filter by user ID. Example: <code>5</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_from"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="2025-01-01"
               data-component="query">
    <br>
<p>Filter requests created from this date. Must be a valid date. Example: <code>2025-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_to"                data-endpoint="GETapi-v1-admin-extra-view-requests"
               value="2025-12-31"
               data-component="query">
    <br>
<p>Filter requests created up to this date. Must be a valid date. Example: <code>2025-12-31</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">POST api/v1/admin/extra-view-requests/{extraViewRequest_id}/approve</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/extra-view-requests/16/approve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"granted_views\": 3,
    \"decision_reason\": \"Verified request validity\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/extra-view-requests/16/approve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "granted_views": 3,
    "decision_reason": "Verified request validity"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">
</span>
<span id="execution-results-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve" data-method="POST"
      data-path="api/v1/admin/extra-view-requests/{extraViewRequest_id}/approve"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
                    onclick="tryItOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
                    onclick="cancelTryOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/extra-view-requests/{extraViewRequest_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>extraViewRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="extraViewRequest_id"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="16"
               data-component="url">
    <br>
<p>The ID of the extraViewRequest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>granted_views</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="granted_views"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="3"
               data-component="body">
    <br>
<p>Number of extra full plays granted. Must be at least 1. Example: <code>3</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>decision_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="decision_reason"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--approve"
               value="Verified request validity"
               data-component="body">
    <br>
<p>Optional reason for approval. Must not be greater than 1000 characters. Example: <code>Verified request validity</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">POST api/v1/admin/extra-view-requests/{extraViewRequest_id}/reject</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/extra-view-requests/16/reject" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"decision_reason\": \"Insufficient justification provided\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/extra-view-requests/16/reject"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "decision_reason": "Insufficient justification provided"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">
</span>
<span id="execution-results-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject" data-method="POST"
      data-path="api/v1/admin/extra-view-requests/{extraViewRequest_id}/reject"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
                    onclick="tryItOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
                    onclick="cancelTryOut('POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/extra-view-requests/{extraViewRequest_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>extraViewRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="extraViewRequest_id"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
               value="16"
               data-component="url">
    <br>
<p>The ID of the extraViewRequest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>decision_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="decision_reason"                data-endpoint="POSTapi-v1-admin-extra-view-requests--extraViewRequest_id--reject"
               value="Insufficient justification provided"
               data-component="body">
    <br>
<p>Optional reason for rejecting the request. Must not be greater than 1000 characters. Example: <code>Insufficient justification provided</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-device-change-requests">GET api/v1/admin/device-change-requests</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-device-change-requests">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/device-change-requests?per_page=15&amp;page=1&amp;status=PENDING&amp;center_id=2&amp;user_id=5&amp;date_from=2025-01-01&amp;date_to=2025-12-31" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/device-change-requests"
);

const params = {
    "per_page": "15",
    "page": "1",
    "status": "PENDING",
    "center_id": "2",
    "user_id": "5",
    "date_from": "2025-01-01",
    "date_to": "2025-12-31",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-device-change-requests">
    </span>
<span id="execution-results-GETapi-v1-admin-device-change-requests" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-device-change-requests"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-device-change-requests"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-device-change-requests" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-device-change-requests">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-device-change-requests" data-method="GET"
      data-path="api/v1/admin/device-change-requests"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-device-change-requests', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-device-change-requests"
                    onclick="tryItOut('GETapi-v1-admin-device-change-requests');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-device-change-requests"
                    onclick="cancelTryOut('GETapi-v1-admin-device-change-requests');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-device-change-requests"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/device-change-requests</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="status"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="PENDING"
               data-component="query">
    <br>
<p>Filter by request status. Example: <code>PENDING</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>PENDING</code></li> <li><code>APPROVED</code></li> <li><code>REJECTED</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="2"
               data-component="query">
    <br>
<p>Filter by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="5"
               data-component="query">
    <br>
<p>Filter by user ID. Example: <code>5</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_from</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_from"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="2025-01-01"
               data-component="query">
    <br>
<p>Filter requests created from this date. Must be a valid date. Example: <code>2025-01-01</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>date_to</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="date_to"                data-endpoint="GETapi-v1-admin-device-change-requests"
               value="2025-12-31"
               data-component="query">
    <br>
<p>Filter requests created up to this date. Must be a valid date. Example: <code>2025-12-31</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">POST api/v1/admin/device-change-requests/{deviceChangeRequest_id}/approve</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/device-change-requests/16/approve" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/device-change-requests/16/approve"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">
</span>
<span id="execution-results-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve" data-method="POST"
      data-path="api/v1/admin/device-change-requests/{deviceChangeRequest_id}/approve"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
                    onclick="tryItOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
                    onclick="cancelTryOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/device-change-requests/{deviceChangeRequest_id}/approve</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deviceChangeRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deviceChangeRequest_id"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--approve"
               value="16"
               data-component="url">
    <br>
<p>The ID of the deviceChangeRequest. Example: <code>16</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">POST api/v1/admin/device-change-requests/{deviceChangeRequest_id}/reject</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/device-change-requests/16/reject" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"decision_reason\": \"Device policy violation\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/device-change-requests/16/reject"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "decision_reason": "Device policy violation"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">
</span>
<span id="execution-results-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject" data-method="POST"
      data-path="api/v1/admin/device-change-requests/{deviceChangeRequest_id}/reject"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
                    onclick="tryItOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
                    onclick="cancelTryOut('POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/device-change-requests/{deviceChangeRequest_id}/reject</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>deviceChangeRequest_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="deviceChangeRequest_id"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
               value="16"
               data-component="url">
    <br>
<p>The ID of the deviceChangeRequest. Example: <code>16</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>decision_reason</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="decision_reason"                data-endpoint="POSTapi-v1-admin-device-change-requests--deviceChangeRequest_id--reject"
               value="Device policy violation"
               data-component="body">
    <br>
<p>Optional reason for rejecting the device change. Must not be greater than 1000 characters. Example: <code>Device policy violation</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-roles">GET api/v1/admin/roles</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-roles">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/roles?per_page=15" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/roles"
);

const params = {
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-roles">
    </span>
<span id="execution-results-GETapi-v1-admin-roles" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-roles"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-roles"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-roles" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-roles">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-roles" data-method="GET"
      data-path="api/v1/admin/roles"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-roles', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-roles"
                    onclick="tryItOut('GETapi-v1-admin-roles');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-roles"
                    onclick="cancelTryOut('GETapi-v1-admin-roles');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-roles"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/roles</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-roles"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-roles"
               value="15"
               data-component="query">
    <br>
<p>Items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-roles">POST api/v1/admin/roles</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-roles">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/roles" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Content Admin\",
    \"slug\": \"content_admin\",
    \"description\": \"Manages course and video content.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/roles"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Content Admin",
    "slug": "content_admin",
    "description": "Manages course and video content."
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-roles">
</span>
<span id="execution-results-POSTapi-v1-admin-roles" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-roles"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-roles"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-roles" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-roles">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-roles" data-method="POST"
      data-path="api/v1/admin/roles"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-roles', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-roles"
                    onclick="tryItOut('POSTapi-v1-admin-roles');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-roles"
                    onclick="cancelTryOut('POSTapi-v1-admin-roles');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-roles"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/roles</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-roles"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-admin-roles"
               value="Content Admin"
               data-component="body">
    <br>
<p>Role display name. Must not be greater than 100 characters. Example: <code>Content Admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="POSTapi-v1-admin-roles"
               value="content_admin"
               data-component="body">
    <br>
<p>Unique role identifier. Must not be greater than 100 characters. Example: <code>content_admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="POSTapi-v1-admin-roles"
               value="Manages course and video content."
               data-component="body">
    <br>
<p>Optional role description. Must not be greater than 255 characters. Example: <code>Manages course and video content.</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-v1-admin-roles--role_id-">PUT api/v1/admin/roles/{role_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-roles--role_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/roles/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Support Admin\",
    \"slug\": \"support_admin\",
    \"description\": \"Handles support workflows.\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/roles/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Support Admin",
    "slug": "support_admin",
    "description": "Handles support workflows."
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-roles--role_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-roles--role_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-roles--role_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-roles--role_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-roles--role_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-roles--role_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-roles--role_id-" data-method="PUT"
      data-path="api/v1/admin/roles/{role_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-roles--role_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-roles--role_id-"
                    onclick="tryItOut('PUTapi-v1-admin-roles--role_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-roles--role_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-roles--role_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-roles--role_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/roles/{role_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>role_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="role_id"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the role. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="Support Admin"
               data-component="body">
    <br>
<p>Role display name. Must not be greater than 100 characters. Example: <code>Support Admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>slug</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="slug"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="support_admin"
               data-component="body">
    <br>
<p>Unique role identifier. Must not be greater than 100 characters. Example: <code>support_admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description"                data-endpoint="PUTapi-v1-admin-roles--role_id-"
               value="Handles support workflows."
               data-component="body">
    <br>
<p>Optional role description. Must not be greater than 255 characters. Example: <code>Handles support workflows.</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-roles--role_id-">DELETE api/v1/admin/roles/{role_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-roles--role_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/roles/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/roles/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-roles--role_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-roles--role_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-roles--role_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-roles--role_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-roles--role_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-roles--role_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-roles--role_id-" data-method="DELETE"
      data-path="api/v1/admin/roles/{role_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-roles--role_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-roles--role_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-roles--role_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-roles--role_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-roles--role_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-roles--role_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/roles/{role_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-roles--role_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-roles--role_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-roles--role_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>role_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="role_id"                data-endpoint="DELETEapi-v1-admin-roles--role_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the role. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-roles--role_id--permissions">PUT api/v1/admin/roles/{role_id}/permissions</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-roles--role_id--permissions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/roles/1/permissions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"permission_ids\": [
        1
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/roles/1/permissions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "permission_ids": [
        1
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-roles--role_id--permissions">
</span>
<span id="execution-results-PUTapi-v1-admin-roles--role_id--permissions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-roles--role_id--permissions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-roles--role_id--permissions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-roles--role_id--permissions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-roles--role_id--permissions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-roles--role_id--permissions" data-method="PUT"
      data-path="api/v1/admin/roles/{role_id}/permissions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-roles--role_id--permissions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-roles--role_id--permissions"
                    onclick="tryItOut('PUTapi-v1-admin-roles--role_id--permissions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-roles--role_id--permissions"
                    onclick="cancelTryOut('PUTapi-v1-admin-roles--role_id--permissions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-roles--role_id--permissions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/roles/{role_id}/permissions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>role_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="role_id"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               value="1"
               data-component="url">
    <br>
<p>The ID of the role. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>permission_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="permission_ids[0]"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               data-component="body">
        <input type="number" style="display: none"
               name="permission_ids[1]"                data-endpoint="PUTapi-v1-admin-roles--role_id--permissions"
               data-component="body">
    <br>
<p>Permission ID. The <code>id</code> of an existing record in the permissions table.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-permissions">GET api/v1/admin/permissions</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-permissions">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/permissions" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/permissions"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-permissions">
    </span>
<span id="execution-results-GETapi-v1-admin-permissions" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-permissions"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-permissions"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-permissions" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-permissions">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-permissions" data-method="GET"
      data-path="api/v1/admin/permissions"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-permissions', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-permissions"
                    onclick="tryItOut('GETapi-v1-admin-permissions');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-permissions"
                    onclick="cancelTryOut('GETapi-v1-admin-permissions');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-permissions"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/permissions</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-permissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-permissions"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-permissions"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETapi-v1-admin-users">GET api/v1/admin/users</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-users">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/users?per_page=15" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/users"
);

const params = {
    "per_page": "15",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-users">
    </span>
<span id="execution-results-GETapi-v1-admin-users" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-users"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-users" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-users" data-method="GET"
      data-path="api/v1/admin/users"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-users', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-users"
                    onclick="tryItOut('GETapi-v1-admin-users');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-users"
                    onclick="cancelTryOut('GETapi-v1-admin-users');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-users"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/users</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-users"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-users"
               value="15"
               data-component="query">
    <br>
<p>Items per page. Example: <code>15</code></p>
            </div>
                </form>

                    <h2 id="endpoints-POSTapi-v1-admin-users">POST api/v1/admin/users</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-users">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/users" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Jane Admin\",
    \"email\": \"jane.admin@example.com\",
    \"phone\": \"19990000003\",
    \"password\": \"secret123\",
    \"status\": 1,
    \"center_id\": 12
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/users"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Jane Admin",
    "email": "jane.admin@example.com",
    "phone": "19990000003",
    "password": "secret123",
    "status": 1,
    "center_id": 12
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-users">
</span>
<span id="execution-results-POSTapi-v1-admin-users" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-users"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-users"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-users" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-users">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-users" data-method="POST"
      data-path="api/v1/admin/users"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-users', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-users"
                    onclick="tryItOut('POSTapi-v1-admin-users');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-users"
                    onclick="cancelTryOut('POSTapi-v1-admin-users');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-users"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/users</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-users"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-users"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-admin-users"
               value="Jane Admin"
               data-component="body">
    <br>
<p>Admin name. Must not be greater than 100 characters. Example: <code>Jane Admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-users"
               value="jane.admin@example.com"
               data-component="body">
    <br>
<p>Admin email address. Must be a valid email address. Must not be greater than 190 characters. Example: <code>jane.admin@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-admin-users"
               value="19990000003"
               data-component="body">
    <br>
<p>Admin phone number. Must not be greater than 30 characters. Example: <code>19990000003</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTapi-v1-admin-users"
               value="secret123"
               data-component="body">
    <br>
<p>Admin password. Must be at least 8 characters. Example: <code>secret123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status"                data-endpoint="POSTapi-v1-admin-users"
               value="1"
               data-component="body">
    <br>
<p>Admin status (0 inactive, 1 active, 2 banned). Example: <code>1</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>0</code></li> <li><code>1</code></li> <li><code>2</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="POSTapi-v1-admin-users"
               value="12"
               data-component="body">
    <br>
<p>Optional center assignment for admin. The <code>id</code> of an existing record in the centers table. Example: <code>12</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTapi-v1-admin-users--user_id-">PUT api/v1/admin/users/{user_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-users--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/users/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Updated Admin\",
    \"email\": \"updated.admin@example.com\",
    \"phone\": \"19990000004\",
    \"password\": \"secret123\",
    \"status\": 1,
    \"center_id\": 12
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/users/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Updated Admin",
    "email": "updated.admin@example.com",
    "phone": "19990000004",
    "password": "secret123",
    "status": 1,
    "center_id": 12
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-users--user_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-users--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-users--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-users--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-users--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-users--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-users--user_id-" data-method="PUT"
      data-path="api/v1/admin/users/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-users--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-users--user_id-"
                    onclick="tryItOut('PUTapi-v1-admin-users--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-users--user_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-users--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-users--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/users/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="Updated Admin"
               data-component="body">
    <br>
<p>Admin name. Must not be greater than 100 characters. Example: <code>Updated Admin</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="updated.admin@example.com"
               data-component="body">
    <br>
<p>Admin email address. Must be a valid email address. Must not be greater than 190 characters. Example: <code>updated.admin@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="19990000004"
               data-component="body">
    <br>
<p>Admin phone number. Must not be greater than 30 characters. Example: <code>19990000004</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="secret123"
               data-component="body">
    <br>
<p>Admin password. Must be at least 8 characters. Example: <code>secret123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="1"
               data-component="body">
    <br>
<p>Admin status (0 inactive, 1 active, 2 banned). Example: <code>1</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>0</code></li> <li><code>1</code></li> <li><code>2</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="PUTapi-v1-admin-users--user_id-"
               value="12"
               data-component="body">
    <br>
<p>Optional center assignment for admin. The <code>id</code> of an existing record in the centers table. Example: <code>12</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-users--user_id-">DELETE api/v1/admin/users/{user_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-users--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/users/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/users/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-users--user_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-users--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-users--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-users--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-users--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-users--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-users--user_id-" data-method="DELETE"
      data-path="api/v1/admin/users/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-users--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-users--user_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-users--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-users--user_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-users--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-users--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/users/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-users--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-users--user_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="DELETEapi-v1-admin-users--user_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-admin-users--user_id--roles">PUT api/v1/admin/users/{user_id}/roles</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-users--user_id--roles">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/users/1/roles" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"role_ids\": [
        1
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/users/1/roles"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "role_ids": [
        1
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-users--user_id--roles">
</span>
<span id="execution-results-PUTapi-v1-admin-users--user_id--roles" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-users--user_id--roles"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-users--user_id--roles"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-users--user_id--roles" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-users--user_id--roles">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-users--user_id--roles" data-method="PUT"
      data-path="api/v1/admin/users/{user_id}/roles"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-users--user_id--roles', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-users--user_id--roles"
                    onclick="tryItOut('PUTapi-v1-admin-users--user_id--roles');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-users--user_id--roles"
                    onclick="cancelTryOut('PUTapi-v1-admin-users--user_id--roles');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-users--user_id--roles"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/users/{user_id}/roles</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               value="1"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>role_ids</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="role_ids[0]"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               data-component="body">
        <input type="number" style="display: none"
               name="role_ids[1]"                data-endpoint="PUTapi-v1-admin-users--user_id--roles"
               data-component="body">
    <br>
<p>Role ID. The <code>id</code> of an existing record in the roles table.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-admin-students">GET api/v1/admin/students</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-admin-students">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/admin/students?per_page=15&amp;page=1&amp;center_id=2&amp;status=1&amp;search=Sara" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/students"
);

const params = {
    "per_page": "15",
    "page": "1",
    "center_id": "2",
    "status": "1",
    "search": "Sara",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-admin-students">
    </span>
<span id="execution-results-GETapi-v1-admin-students" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-admin-students"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-admin-students"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-admin-students" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-admin-students">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-admin-students" data-method="GET"
      data-path="api/v1/admin/students"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-admin-students', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-admin-students"
                    onclick="tryItOut('GETapi-v1-admin-students');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-admin-students"
                    onclick="cancelTryOut('GETapi-v1-admin-students');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-admin-students"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/admin/students</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-admin-students"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-admin-students"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="GETapi-v1-admin-students"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETapi-v1-admin-students"
               value="15"
               data-component="query">
    <br>
<p>Items per page (max 100). Must be at least 1. Must not be greater than 100. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETapi-v1-admin-students"
               value="1"
               data-component="query">
    <br>
<p>Page number to retrieve. Must be at least 1. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="GETapi-v1-admin-students"
               value="2"
               data-component="query">
    <br>
<p>Filter students by center ID (super admin only). Example: <code>2</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status"                data-endpoint="GETapi-v1-admin-students"
               value="1"
               data-component="query">
    <br>
<p>Filter students by status (0 inactive, 1 active, 2 banned). Example: <code>1</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>0</code></li> <li><code>1</code></li> <li><code>2</code></li></ul>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETapi-v1-admin-students"
               value="Sara"
               data-component="query">
    <br>
<p>Search students by name, username, or email. Example: <code>Sara</code></p>
            </div>
                </form>

                    <h2 id="endpoints-PUTapi-v1-admin-students--user_id-">PUT api/v1/admin/students/{user_id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-admin-students--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/admin/students/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Student One\",
    \"email\": \"student@example.com\",
    \"status\": 1
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/students/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Student One",
    "email": "student@example.com",
    "status": 1
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-admin-students--user_id-">
</span>
<span id="execution-results-PUTapi-v1-admin-students--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-admin-students--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-admin-students--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-admin-students--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-admin-students--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-admin-students--user_id-" data-method="PUT"
      data-path="api/v1/admin/students/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-admin-students--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-admin-students--user_id-"
                    onclick="tryItOut('PUTapi-v1-admin-students--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-admin-students--user_id-"
                    onclick="cancelTryOut('PUTapi-v1-admin-students--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-admin-students--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/admin/students/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>1</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="Student One"
               data-component="body">
    <br>
<p>Student name. Must not be greater than 100 characters. Example: <code>Student One</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="student@example.com"
               data-component="body">
    <br>
<p>Student email address. Must be a valid email address. Must not be greater than 190 characters. Example: <code>student@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="status"                data-endpoint="PUTapi-v1-admin-students--user_id-"
               value="1"
               data-component="body">
    <br>
<p>Student status (0 inactive, 1 active, 2 banned). Example: <code>1</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>0</code></li> <li><code>1</code></li> <li><code>2</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-POSTapi-v1-admin-students">POST api/v1/admin/students</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-admin-students">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/admin/students" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"name\": \"Student One\",
    \"email\": \"student@example.com\",
    \"phone\": \"19990000001\",
    \"country_code\": \"+20\",
    \"center_id\": 12
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/students"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "name": "Student One",
    "email": "student@example.com",
    "phone": "19990000001",
    "country_code": "+20",
    "center_id": 12
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-admin-students">
</span>
<span id="execution-results-POSTapi-v1-admin-students" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-admin-students"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-admin-students"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-admin-students" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-admin-students">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-admin-students" data-method="POST"
      data-path="api/v1/admin/students"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-admin-students', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-admin-students"
                    onclick="tryItOut('POSTapi-v1-admin-students');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-admin-students"
                    onclick="cancelTryOut('POSTapi-v1-admin-students');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-admin-students"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/admin/students</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-admin-students"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-admin-students"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="POSTapi-v1-admin-students"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="name"                data-endpoint="POSTapi-v1-admin-students"
               value="Student One"
               data-component="body">
    <br>
<p>Student name. Must not be greater than 100 characters. Example: <code>Student One</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-admin-students"
               value="student@example.com"
               data-component="body">
    <br>
<p>Student email address. Must be a valid email address. Must not be greater than 190 characters. Example: <code>student@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-admin-students"
               value="19990000001"
               data-component="body">
    <br>
<p>Student phone number. Must not be greater than 30 characters. Example: <code>19990000001</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>country_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="country_code"                data-endpoint="POSTapi-v1-admin-students"
               value="+20"
               data-component="body">
    <br>
<p>Student country code. Must not be greater than 8 characters. Example: <code>+20</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>center_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="center_id"                data-endpoint="POSTapi-v1-admin-students"
               value="12"
               data-component="body">
    <br>
<p>Optional center assignment for student. The <code>id</code> of an existing record in the centers table. Example: <code>12</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-admin-students--user_id-">DELETE api/v1/admin/students/{user_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-admin-students--user_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/admin/students/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/admin/students/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-admin-students--user_id-">
</span>
<span id="execution-results-DELETEapi-v1-admin-students--user_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-admin-students--user_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-admin-students--user_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-admin-students--user_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-admin-students--user_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-admin-students--user_id-" data-method="DELETE"
      data-path="api/v1/admin/students/{user_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-admin-students--user_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-admin-students--user_id-"
                    onclick="tryItOut('DELETEapi-v1-admin-students--user_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-admin-students--user_id-"
                    onclick="cancelTryOut('DELETEapi-v1-admin-students--user_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-admin-students--user_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/admin/students/{user_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-admin-students--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="DELETEapi-v1-admin-students--user_id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-Locale</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-Locale"                data-endpoint="DELETEapi-v1-admin-students--user_id-"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>user_id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="user_id"                data-endpoint="DELETEapi-v1-admin-students--user_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the user. Example: <code>1</code></p>
            </div>
                    </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
