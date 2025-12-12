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
                                                    <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-send-otp">
                                <a href="#endpoints-POSTapi-v1-auth-send-otp">POST api/v1/auth/send-otp</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-verify">
                                <a href="#endpoints-POSTapi-v1-auth-verify">POST api/v1/auth/verify</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-auth-refresh">
                                <a href="#endpoints-POSTapi-v1-auth-refresh">POST api/v1/auth/refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses">
                                <a href="#endpoints-GETapi-v1-courses">GET api/v1/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id-">GET api/v1/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-instructors">
                                <a href="#endpoints-GETapi-v1-instructors">GET api/v1/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-instructors">
                                <a href="#endpoints-POSTapi-v1-instructors">POST api/v1/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-instructors--id-">
                                <a href="#endpoints-GETapi-v1-instructors--id-">GET api/v1/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTapi-v1-instructors--id-">
                                <a href="#endpoints-PUTapi-v1-instructors--id-">PUT api/v1/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-instructors--id-">
                                <a href="#endpoints-DELETEapi-v1-instructors--id-">DELETE api/v1/instructors/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTapi-v1-courses--course_id--instructors">
                                <a href="#endpoints-POSTapi-v1-courses--course_id--instructors">POST api/v1/courses/{course_id}/instructors</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEapi-v1-courses--course_id--instructors--instructor_id-">
                                <a href="#endpoints-DELETEapi-v1-courses--course_id--instructors--instructor_id-">DELETE api/v1/courses/{course_id}/instructors/{instructor_id}</a>
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
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--pdfs">
                                <a href="#endpoints-GETapi-v1-courses--course_id--pdfs">GET api/v1/courses/{course_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETapi-v1-courses--course_id--sections--section_id--pdfs--pdf_id-">GET api/v1/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-auth-login">
                                <a href="#endpoints-POSTadmin-auth-login">POST admin/auth/login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-auth-me">
                                <a href="#endpoints-GETadmin-auth-me">GET admin/auth/me</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-auth-refresh">
                                <a href="#endpoints-POSTadmin-auth-refresh">POST admin/auth/refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-auth-logout">
                                <a href="#endpoints-POSTadmin-auth-logout">POST admin/auth/logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses">
                                <a href="#endpoints-GETadmin-courses">GET admin/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses">
                                <a href="#endpoints-POSTadmin-courses">POST admin/courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id-">
                                <a href="#endpoints-GETadmin-courses--course_id-">GET admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTadmin-courses--course_id-">
                                <a href="#endpoints-PUTadmin-courses--course_id-">PUT admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course_id-">
                                <a href="#endpoints-DELETEadmin-courses--course_id-">DELETE admin/courses/{course_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--publish">
                                <a href="#endpoints-POSTadmin-courses--course_id--publish">POST admin/courses/{course_id}/publish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--clone">
                                <a href="#endpoints-POSTadmin-courses--course_id--clone">POST admin/courses/{course_id}/clone</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections">
                                <a href="#endpoints-GETadmin-courses--course_id--sections">GET admin/courses/{course_id}/sections</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course--sections">
                                <a href="#endpoints-POSTadmin-courses--course--sections">POST admin/courses/{course}/sections</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTadmin-courses--course_id--sections-reorder">
                                <a href="#endpoints-PUTadmin-courses--course_id--sections-reorder">PUT admin/courses/{course_id}/sections/reorder</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections--section_id-">
                                <a href="#endpoints-GETadmin-courses--course_id--sections--section_id-">GET admin/courses/{course_id}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTadmin-courses--course--sections--section_id-">
                                <a href="#endpoints-PUTadmin-courses--course--sections--section_id-">PUT admin/courses/{course}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course--sections--section_id-">
                                <a href="#endpoints-DELETEadmin-courses--course--sections--section_id-">DELETE admin/courses/{course}/sections/{section_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course--sections--section_id--restore">
                                <a href="#endpoints-POSTadmin-courses--course--sections--section_id--restore">POST admin/courses/{course}/sections/{section_id}/restore</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PATCHadmin-courses--course_id--sections--section_id--visibility">
                                <a href="#endpoints-PATCHadmin-courses--course_id--sections--section_id--visibility">PATCH admin/courses/{course_id}/sections/{section_id}/visibility</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course--sections-structure">
                                <a href="#endpoints-POSTadmin-courses--course--sections-structure">POST admin/courses/{course}/sections/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-PUTadmin-courses--course--sections--section_id--structure">
                                <a href="#endpoints-PUTadmin-courses--course--sections--section_id--structure">PUT admin/courses/{course}/sections/{section_id}/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course--sections--section_id--structure">
                                <a href="#endpoints-DELETEadmin-courses--course--sections--section_id--structure">DELETE admin/courses/{course}/sections/{section_id}/structure</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections--section_id--videos">
                                <a href="#endpoints-GETadmin-courses--course_id--sections--section_id--videos">GET admin/courses/{course_id}/sections/{section_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections--section_id--videos--video_id-">
                                <a href="#endpoints-GETadmin-courses--course_id--sections--section_id--videos--video_id-">GET admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--sections--section_id--videos">
                                <a href="#endpoints-POSTadmin-courses--course_id--sections--section_id--videos">POST admin/courses/{course_id}/sections/{section_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">
                                <a href="#endpoints-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">DELETE admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-GETadmin-courses--course_id--sections--section_id--pdfs">GET admin/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">GET admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--sections--section_id--pdfs">
                                <a href="#endpoints-POSTadmin-courses--course_id--sections--section_id--pdfs">POST admin/courses/{course_id}/sections/{section_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
                                <a href="#endpoints-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">DELETE admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course--sections--section_id--publish">
                                <a href="#endpoints-POSTadmin-courses--course--sections--section_id--publish">POST admin/courses/{course}/sections/{section_id}/publish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course--sections--section_id--unpublish">
                                <a href="#endpoints-POSTadmin-courses--course--sections--section_id--unpublish">POST admin/courses/{course}/sections/{section_id}/unpublish</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--videos">
                                <a href="#endpoints-POSTadmin-courses--course_id--videos">POST admin/courses/{course_id}/videos</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course_id--videos--video-">
                                <a href="#endpoints-DELETEadmin-courses--course_id--videos--video-">DELETE admin/courses/{course_id}/videos/{video}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTadmin-courses--course_id--pdfs">
                                <a href="#endpoints-POSTadmin-courses--course_id--pdfs">POST admin/courses/{course_id}/pdfs</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-DELETEadmin-courses--course_id--pdfs--pdf-">
                                <a href="#endpoints-DELETEadmin-courses--course_id--pdfs--pdf-">DELETE admin/courses/{course_id}/pdfs/{pdf}</a>
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
        <li>Last updated: December 12, 2025</li>
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

                    <h2 id="endpoints-GETapi-v1-courses">GET api/v1/courses</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/courses" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses"
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

                    <h2 id="endpoints-GETapi-v1-instructors">GET api/v1/instructors</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/api/v1/instructors" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/instructors"
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
                        </form>

                    <h2 id="endpoints-POSTapi-v1-instructors">POST api/v1/instructors</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/instructors" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"center_id\": 1,
    \"name_translations\": {
        \"en\": \"John Doe\",
        \"ar\": \"جون دو\"
    },
    \"bio_translations\": {
        \"en\": \"Senior instructor\",
        \"ar\": \"مدرب كبير\"
    },
    \"title_translations\": {
        \"en\": \"Professor\",
        \"ar\": \"أستاذ\"
    },
    \"avatar_url\": \"https:\\/\\/example.com\\/avatar.jpg\",
    \"email\": \"john.doe@example.com\",
    \"phone\": \"+1234567890\",
    \"social_links\": [
        \"architecto\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/instructors"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "center_id": 1,
    "name_translations": {
        "en": "John Doe",
        "ar": "جون دو"
    },
    "bio_translations": {
        "en": "Senior instructor",
        "ar": "مدرب كبير"
    },
    "title_translations": {
        "en": "Professor",
        "ar": "أستاذ"
    },
    "avatar_url": "https:\/\/example.com\/avatar.jpg",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "social_links": [
        "architecto"
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-instructors">
</span>
<span id="execution-results-POSTapi-v1-instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-instructors" data-method="POST"
      data-path="api/v1/instructors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-instructors"
                    onclick="tryItOut('POSTapi-v1-instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-instructors"
                    onclick="cancelTryOut('POSTapi-v1-instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-instructors"
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
                              name="Accept"                data-endpoint="POSTapi-v1-instructors"
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
                              name="X-Locale"                data-endpoint="POSTapi-v1-instructors"
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
               step="any"               name="center_id"                data-endpoint="POSTapi-v1-instructors"
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
                              name="name_translations"                data-endpoint="POSTapi-v1-instructors"
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
                              name="bio_translations"                data-endpoint="POSTapi-v1-instructors"
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
                              name="title_translations"                data-endpoint="POSTapi-v1-instructors"
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
                              name="avatar_url"                data-endpoint="POSTapi-v1-instructors"
               value="https://example.com/avatar.jpg"
               data-component="body">
    <br>
<p>Profile image URL. Example: <code>https://example.com/avatar.jpg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-instructors"
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
                              name="phone"                data-endpoint="POSTapi-v1-instructors"
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
                              name="social_links[0]"                data-endpoint="POSTapi-v1-instructors"
               data-component="body">
        <input type="text" style="display: none"
               name="social_links[1]"                data-endpoint="POSTapi-v1-instructors"
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="endpoints-GETapi-v1-instructors--id-">GET api/v1/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-GETapi-v1-instructors--id-">
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

<span id="example-responses-GETapi-v1-instructors--id-">
    </span>
<span id="execution-results-GETapi-v1-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-instructors--id-" data-method="GET"
      data-path="api/v1/instructors/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-instructors--id-"
                    onclick="tryItOut('GETapi-v1-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-instructors--id-"
                    onclick="cancelTryOut('GETapi-v1-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-instructors--id-"
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
                              name="Accept"                data-endpoint="GETapi-v1-instructors--id-"
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
                              name="X-Locale"                data-endpoint="GETapi-v1-instructors--id-"
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
               step="any"               name="id"                data-endpoint="GETapi-v1-instructors--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTapi-v1-instructors--id-">PUT api/v1/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-PUTapi-v1-instructors--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/api/v1/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"center_id\": 1,
    \"name_translations\": {
        \"en\": \"John Doe\",
        \"ar\": \"جون دو\"
    },
    \"bio_translations\": {
        \"en\": \"Senior instructor\",
        \"ar\": \"مدرب كبير\"
    },
    \"title_translations\": {
        \"en\": \"Professor\",
        \"ar\": \"أستاذ\"
    },
    \"avatar_url\": \"https:\\/\\/example.com\\/avatar.jpg\",
    \"email\": \"john.doe@example.com\",
    \"phone\": \"+1234567890\",
    \"social_links\": [
        \"architecto\"
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/instructors/1"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "center_id": 1,
    "name_translations": {
        "en": "John Doe",
        "ar": "جون دو"
    },
    "bio_translations": {
        "en": "Senior instructor",
        "ar": "مدرب كبير"
    },
    "title_translations": {
        "en": "Professor",
        "ar": "أستاذ"
    },
    "avatar_url": "https:\/\/example.com\/avatar.jpg",
    "email": "john.doe@example.com",
    "phone": "+1234567890",
    "social_links": [
        "architecto"
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTapi-v1-instructors--id-">
</span>
<span id="execution-results-PUTapi-v1-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTapi-v1-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTapi-v1-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTapi-v1-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTapi-v1-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTapi-v1-instructors--id-" data-method="PUT"
      data-path="api/v1/instructors/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTapi-v1-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTapi-v1-instructors--id-"
                    onclick="tryItOut('PUTapi-v1-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTapi-v1-instructors--id-"
                    onclick="cancelTryOut('PUTapi-v1-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTapi-v1-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>api/v1/instructors/{id}</code></b>
        </p>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>api/v1/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="Accept"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="X-Locale"                data-endpoint="PUTapi-v1-instructors--id-"
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
               step="any"               name="id"                data-endpoint="PUTapi-v1-instructors--id-"
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
               step="any"               name="center_id"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="name_translations"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="bio_translations"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="title_translations"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="avatar_url"                data-endpoint="PUTapi-v1-instructors--id-"
               value="https://example.com/avatar.jpg"
               data-component="body">
    <br>
<p>Profile image URL. Example: <code>https://example.com/avatar.jpg</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="phone"                data-endpoint="PUTapi-v1-instructors--id-"
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
                              name="social_links[0]"                data-endpoint="PUTapi-v1-instructors--id-"
               data-component="body">
        <input type="text" style="display: none"
               name="social_links[1]"                data-endpoint="PUTapi-v1-instructors--id-"
               data-component="body">
    <br>

        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-instructors--id-">DELETE api/v1/instructors/{id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-instructors--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/instructors/1" \
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
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-DELETEapi-v1-instructors--id-">
</span>
<span id="execution-results-DELETEapi-v1-instructors--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-instructors--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-instructors--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-instructors--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-instructors--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-instructors--id-" data-method="DELETE"
      data-path="api/v1/instructors/{id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-instructors--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-instructors--id-"
                    onclick="tryItOut('DELETEapi-v1-instructors--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-instructors--id-"
                    onclick="cancelTryOut('DELETEapi-v1-instructors--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-instructors--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/instructors/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-instructors--id-"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-instructors--id-"
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
                              name="X-Locale"                data-endpoint="DELETEapi-v1-instructors--id-"
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
               step="any"               name="id"                data-endpoint="DELETEapi-v1-instructors--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTapi-v1-courses--course_id--instructors">POST api/v1/courses/{course_id}/instructors</h2>

<p>
</p>



<span id="example-requests-POSTapi-v1-courses--course_id--instructors">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/api/v1/courses/1/instructors" \
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
    "http://xyz-lms.test/api/v1/courses/1/instructors"
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

<span id="example-responses-POSTapi-v1-courses--course_id--instructors">
</span>
<span id="execution-results-POSTapi-v1-courses--course_id--instructors" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-courses--course_id--instructors"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-courses--course_id--instructors"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-courses--course_id--instructors" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-courses--course_id--instructors">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-courses--course_id--instructors" data-method="POST"
      data-path="api/v1/courses/{course_id}/instructors"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-courses--course_id--instructors', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-courses--course_id--instructors"
                    onclick="tryItOut('POSTapi-v1-courses--course_id--instructors');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-courses--course_id--instructors"
                    onclick="cancelTryOut('POSTapi-v1-courses--course_id--instructors');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-courses--course_id--instructors"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/courses/{course_id}/instructors</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
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
                              name="Accept"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
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
                              name="X-Locale"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
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
               step="any"               name="course_id"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
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
               step="any"               name="instructor_id"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
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
                              name="role"                data-endpoint="POSTapi-v1-courses--course_id--instructors"
               value="assistant"
               data-component="body">
    <br>
<p>Optional role for this instructor within the course. Example: <code>assistant</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEapi-v1-courses--course_id--instructors--instructor_id-">DELETE api/v1/courses/{course_id}/instructors/{instructor_id}</h2>

<p>
</p>



<span id="example-requests-DELETEapi-v1-courses--course_id--instructors--instructor_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/api/v1/courses/1/instructors/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/api/v1/courses/1/instructors/1"
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

<span id="example-responses-DELETEapi-v1-courses--course_id--instructors--instructor_id-">
</span>
<span id="execution-results-DELETEapi-v1-courses--course_id--instructors--instructor_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEapi-v1-courses--course_id--instructors--instructor_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEapi-v1-courses--course_id--instructors--instructor_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEapi-v1-courses--course_id--instructors--instructor_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEapi-v1-courses--course_id--instructors--instructor_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEapi-v1-courses--course_id--instructors--instructor_id-" data-method="DELETE"
      data-path="api/v1/courses/{course_id}/instructors/{instructor_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEapi-v1-courses--course_id--instructors--instructor_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEapi-v1-courses--course_id--instructors--instructor_id-"
                    onclick="tryItOut('DELETEapi-v1-courses--course_id--instructors--instructor_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEapi-v1-courses--course_id--instructors--instructor_id-"
                    onclick="cancelTryOut('DELETEapi-v1-courses--course_id--instructors--instructor_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEapi-v1-courses--course_id--instructors--instructor_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>api/v1/courses/{course_id}/instructors/{instructor_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEapi-v1-courses--course_id--instructors--instructor_id-"
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
                              name="Accept"                data-endpoint="DELETEapi-v1-courses--course_id--instructors--instructor_id-"
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
                              name="X-Locale"                data-endpoint="DELETEapi-v1-courses--course_id--instructors--instructor_id-"
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
               step="any"               name="course_id"                data-endpoint="DELETEapi-v1-courses--course_id--instructors--instructor_id-"
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
               step="any"               name="instructor_id"                data-endpoint="DELETEapi-v1-courses--course_id--instructors--instructor_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the instructor. Example: <code>1</code></p>
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

                    <h2 id="endpoints-POSTadmin-auth-login">POST admin/auth/login</h2>

<p>
</p>



<span id="example-requests-POSTadmin-auth-login">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/auth/login" \
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
    "http://xyz-lms.test/admin/auth/login"
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

<span id="example-responses-POSTadmin-auth-login">
</span>
<span id="execution-results-POSTadmin-auth-login" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-auth-login"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-auth-login"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-auth-login" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-auth-login">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-auth-login" data-method="POST"
      data-path="admin/auth/login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-auth-login', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-auth-login"
                    onclick="tryItOut('POSTadmin-auth-login');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-auth-login"
                    onclick="cancelTryOut('POSTadmin-auth-login');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-auth-login"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/auth/login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-auth-login"
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
                              name="Accept"                data-endpoint="POSTadmin-auth-login"
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
                              name="X-Locale"                data-endpoint="POSTadmin-auth-login"
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
                              name="email"                data-endpoint="POSTadmin-auth-login"
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
                              name="password"                data-endpoint="POSTadmin-auth-login"
               value="admin123"
               data-component="body">
    <br>
<p>Admin password. Example: <code>admin123</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETadmin-auth-me">GET admin/auth/me</h2>

<p>
</p>



<span id="example-requests-GETadmin-auth-me">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/auth/me" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/auth/me"
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

<span id="example-responses-GETadmin-auth-me">
    </span>
<span id="execution-results-GETadmin-auth-me" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-auth-me"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-auth-me"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-auth-me" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-auth-me">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-auth-me" data-method="GET"
      data-path="admin/auth/me"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-auth-me', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-auth-me"
                    onclick="tryItOut('GETadmin-auth-me');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-auth-me"
                    onclick="cancelTryOut('GETadmin-auth-me');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-auth-me"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/auth/me</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-auth-me"
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
                              name="Accept"                data-endpoint="GETadmin-auth-me"
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
                              name="X-Locale"                data-endpoint="GETadmin-auth-me"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTadmin-auth-refresh">POST admin/auth/refresh</h2>

<p>
</p>



<span id="example-requests-POSTadmin-auth-refresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/auth/refresh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/auth/refresh"
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

<span id="example-responses-POSTadmin-auth-refresh">
</span>
<span id="execution-results-POSTadmin-auth-refresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-auth-refresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-auth-refresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-auth-refresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-auth-refresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-auth-refresh" data-method="POST"
      data-path="admin/auth/refresh"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-auth-refresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-auth-refresh"
                    onclick="tryItOut('POSTadmin-auth-refresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-auth-refresh"
                    onclick="cancelTryOut('POSTadmin-auth-refresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-auth-refresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/auth/refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-auth-refresh"
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
                              name="Accept"                data-endpoint="POSTadmin-auth-refresh"
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
                              name="X-Locale"                data-endpoint="POSTadmin-auth-refresh"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTadmin-auth-logout">POST admin/auth/logout</h2>

<p>
</p>



<span id="example-requests-POSTadmin-auth-logout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/auth/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/auth/logout"
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

<span id="example-responses-POSTadmin-auth-logout">
</span>
<span id="execution-results-POSTadmin-auth-logout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-auth-logout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-auth-logout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-auth-logout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-auth-logout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-auth-logout" data-method="POST"
      data-path="admin/auth/logout"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-auth-logout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-auth-logout"
                    onclick="tryItOut('POSTadmin-auth-logout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-auth-logout"
                    onclick="cancelTryOut('POSTadmin-auth-logout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-auth-logout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/auth/logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-auth-logout"
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
                              name="Accept"                data-endpoint="POSTadmin-auth-logout"
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
                              name="X-Locale"                data-endpoint="POSTadmin-auth-logout"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-GETadmin-courses">GET admin/courses</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses"
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

<span id="example-responses-GETadmin-courses">
    </span>
<span id="execution-results-GETadmin-courses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses" data-method="GET"
      data-path="admin/courses"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses"
                    onclick="tryItOut('GETadmin-courses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses"
                    onclick="cancelTryOut('GETadmin-courses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses"
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
                              name="Accept"                data-endpoint="GETadmin-courses"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses"
               value="en"
               data-component="header">
    <br>
<p>Example: <code>en</code></p>
            </div>
                        </form>

                    <h2 id="endpoints-POSTadmin-courses">POST admin/courses</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses" \
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
        \"b\"
    ],
    \"description_translations\": [
        \"architecto\"
    ],
    \"difficulty_level\": 1,
    \"created_by\": 5
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses"
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
        "b"
    ],
    "description_translations": [
        "architecto"
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

<span id="example-responses-POSTadmin-courses">
</span>
<span id="execution-results-POSTadmin-courses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses" data-method="POST"
      data-path="admin/courses"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses"
                    onclick="tryItOut('POSTadmin-courses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses"
                    onclick="cancelTryOut('POSTadmin-courses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses"
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
                              name="Accept"                data-endpoint="POSTadmin-courses"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses"
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
                              name="title"                data-endpoint="POSTadmin-courses"
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
                              name="description"                data-endpoint="POSTadmin-courses"
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
                              name="category_id"                data-endpoint="POSTadmin-courses"
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
                              name="center_id"                data-endpoint="POSTadmin-courses"
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
                              name="difficulty"                data-endpoint="POSTadmin-courses"
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
                              name="language"                data-endpoint="POSTadmin-courses"
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
               step="any"               name="price"                data-endpoint="POSTadmin-courses"
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
                              name="metadata"                data-endpoint="POSTadmin-courses"
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
                              name="title_translations[0]"                data-endpoint="POSTadmin-courses"
               data-component="body">
        <input type="text" style="display: none"
               name="title_translations[1]"                data-endpoint="POSTadmin-courses"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations[0]"                data-endpoint="POSTadmin-courses"
               data-component="body">
        <input type="text" style="display: none"
               name="description_translations[1]"                data-endpoint="POSTadmin-courses"
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty_level</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="difficulty_level"                data-endpoint="POSTadmin-courses"
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
               step="any"               name="created_by"                data-endpoint="POSTadmin-courses"
               value="5"
               data-component="body">
    <br>
<p>User ID creating the course. The <code>id</code> of an existing record in the users table. Example: <code>5</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETadmin-courses--course_id-">GET admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1"
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

<span id="example-responses-GETadmin-courses--course_id-">
    </span>
<span id="execution-results-GETadmin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id-" data-method="GET"
      data-path="admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id-"
                    onclick="tryItOut('GETadmin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id-"
                    onclick="cancelTryOut('GETadmin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id-"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id-"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id-"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTadmin-courses--course_id-">PUT admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-PUTadmin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/admin/courses/1" \
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
        \"b\"
    ],
    \"description_translations\": [
        \"architecto\"
    ],
    \"difficulty_level\": 2,
    \"created_by\": 5
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1"
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
        "b"
    ],
    "description_translations": [
        "architecto"
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

<span id="example-responses-PUTadmin-courses--course_id-">
</span>
<span id="execution-results-PUTadmin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTadmin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTadmin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTadmin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTadmin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTadmin-courses--course_id-" data-method="PUT"
      data-path="admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTadmin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTadmin-courses--course_id-"
                    onclick="tryItOut('PUTadmin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTadmin-courses--course_id-"
                    onclick="cancelTryOut('PUTadmin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTadmin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="Accept"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="X-Locale"                data-endpoint="PUTadmin-courses--course_id-"
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
               step="any"               name="course_id"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="title"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="description"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="category_id"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="center_id"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="difficulty"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="language"                data-endpoint="PUTadmin-courses--course_id-"
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
               step="any"               name="price"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="metadata"                data-endpoint="PUTadmin-courses--course_id-"
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
                              name="title_translations[0]"                data-endpoint="PUTadmin-courses--course_id-"
               data-component="body">
        <input type="text" style="display: none"
               name="title_translations[1]"                data-endpoint="PUTadmin-courses--course_id-"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>description_translations</code></b>&nbsp;&nbsp;
<small>string[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="description_translations[0]"                data-endpoint="PUTadmin-courses--course_id-"
               data-component="body">
        <input type="text" style="display: none"
               name="description_translations[1]"                data-endpoint="PUTadmin-courses--course_id-"
               data-component="body">
    <br>

        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>difficulty_level</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="difficulty_level"                data-endpoint="PUTadmin-courses--course_id-"
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
               step="any"               name="created_by"                data-endpoint="PUTadmin-courses--course_id-"
               value="5"
               data-component="body">
    <br>
<p>User ID updating the course. The <code>id</code> of an existing record in the users table. Example: <code>5</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course_id-">DELETE admin/courses/{course_id}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1"
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

<span id="example-responses-DELETEadmin-courses--course_id-">
</span>
<span id="execution-results-DELETEadmin-courses--course_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course_id-" data-method="DELETE"
      data-path="admin/courses/{course_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course_id-"
                    onclick="tryItOut('DELETEadmin-courses--course_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course_id-"
                    onclick="cancelTryOut('DELETEadmin-courses--course_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course_id-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course_id-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course_id-"
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
               step="any"               name="course_id"                data-endpoint="DELETEadmin-courses--course_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--publish">POST admin/courses/{course_id}/publish</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/publish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/publish"
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

<span id="example-responses-POSTadmin-courses--course_id--publish">
</span>
<span id="execution-results-POSTadmin-courses--course_id--publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--publish" data-method="POST"
      data-path="admin/courses/{course_id}/publish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--publish"
                    onclick="tryItOut('POSTadmin-courses--course_id--publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--publish"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--publish"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--publish"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--publish"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--publish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--clone">POST admin/courses/{course_id}/clone</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--clone">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/clone" \
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
    "http://xyz-lms.test/admin/courses/1/clone"
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

<span id="example-responses-POSTadmin-courses--course_id--clone">
</span>
<span id="execution-results-POSTadmin-courses--course_id--clone" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--clone"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--clone"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--clone" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--clone">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--clone" data-method="POST"
      data-path="admin/courses/{course_id}/clone"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--clone', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--clone"
                    onclick="tryItOut('POSTadmin-courses--course_id--clone');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--clone"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--clone');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--clone"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/clone</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--clone"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--clone"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--clone"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--clone"
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
                <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_sections"
                   value="true"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_sections"
                   value="false"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>include_videos</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_videos"
                   value="true"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_videos"
                   value="false"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>include_pdfs</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_pdfs"
                   value="true"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTadmin-courses--course_id--clone" style="display: none">
            <input type="radio" name="options.include_pdfs"
                   value="false"
                   data-endpoint="POSTadmin-courses--course_id--clone"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections">GET admin/courses/{course_id}/sections</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections"
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

<span id="example-responses-GETadmin-courses--course_id--sections">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections" data-method="GET"
      data-path="admin/courses/{course_id}/sections"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections"
                    onclick="tryItOut('GETadmin-courses--course_id--sections');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course--sections">POST admin/courses/{course}/sections</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course--sections">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections" \
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
    "http://xyz-lms.test/admin/courses/1/sections"
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

<span id="example-responses-POSTadmin-courses--course--sections">
</span>
<span id="execution-results-POSTadmin-courses--course--sections" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course--sections"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course--sections"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course--sections" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course--sections">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course--sections" data-method="POST"
      data-path="admin/courses/{course}/sections"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course--sections', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course--sections"
                    onclick="tryItOut('POSTadmin-courses--course--sections');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course--sections"
                    onclick="cancelTryOut('POSTadmin-courses--course--sections');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course--sections"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course}/sections</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course--sections"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course--sections"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course--sections"
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
               step="any"               name="course"                data-endpoint="POSTadmin-courses--course--sections"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course--sections"
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
                              name="title"                data-endpoint="POSTadmin-courses--course--sections"
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
                              name="description"                data-endpoint="POSTadmin-courses--course--sections"
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
               step="any"               name="sort_order"                data-endpoint="POSTadmin-courses--course--sections"
               value="1"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="endpoints-PUTadmin-courses--course_id--sections-reorder">PUT admin/courses/{course_id}/sections/reorder</h2>

<p>
</p>



<span id="example-requests-PUTadmin-courses--course_id--sections-reorder">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/admin/courses/1/sections/reorder" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"sections\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/reorder"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Locale": "en",
};

let body = {
    "sections": [
        16
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTadmin-courses--course_id--sections-reorder">
</span>
<span id="execution-results-PUTadmin-courses--course_id--sections-reorder" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTadmin-courses--course_id--sections-reorder"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTadmin-courses--course_id--sections-reorder"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTadmin-courses--course_id--sections-reorder" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTadmin-courses--course_id--sections-reorder">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTadmin-courses--course_id--sections-reorder" data-method="PUT"
      data-path="admin/courses/{course_id}/sections/reorder"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTadmin-courses--course_id--sections-reorder', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTadmin-courses--course_id--sections-reorder"
                    onclick="tryItOut('PUTadmin-courses--course_id--sections-reorder');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTadmin-courses--course_id--sections-reorder"
                    onclick="cancelTryOut('PUTadmin-courses--course_id--sections-reorder');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTadmin-courses--course_id--sections-reorder"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>admin/courses/{course_id}/sections/reorder</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
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
                              name="Accept"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
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
                              name="X-Locale"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
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
               step="any"               name="course_id"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
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
               step="any"               name="sections[0]"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
               data-component="body">
        <input type="number" style="display: none"
               name="sections[1]"                data-endpoint="PUTadmin-courses--course_id--sections-reorder"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the sections table.</p>
        </div>
        </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections--section_id-">GET admin/courses/{course_id}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1"
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

<span id="example-responses-GETadmin-courses--course_id--sections--section_id-">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections--section_id-" data-method="GET"
      data-path="admin/courses/{course_id}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections--section_id-"
                    onclick="tryItOut('GETadmin-courses--course_id--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections--section_id-"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections--section_id-"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections--section_id-"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections--section_id-"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id-"
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
               step="any"               name="section_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PUTadmin-courses--course--sections--section_id-">PUT admin/courses/{course}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-PUTadmin-courses--course--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/admin/courses/1/sections/1" \
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
    "http://xyz-lms.test/admin/courses/1/sections/1"
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

<span id="example-responses-PUTadmin-courses--course--sections--section_id-">
</span>
<span id="execution-results-PUTadmin-courses--course--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTadmin-courses--course--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTadmin-courses--course--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTadmin-courses--course--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTadmin-courses--course--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTadmin-courses--course--sections--section_id-" data-method="PUT"
      data-path="admin/courses/{course}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTadmin-courses--course--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTadmin-courses--course--sections--section_id-"
                    onclick="tryItOut('PUTadmin-courses--course--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTadmin-courses--course--sections--section_id-"
                    onclick="cancelTryOut('PUTadmin-courses--course--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTadmin-courses--course--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>admin/courses/{course}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
                              name="Accept"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
                              name="X-Locale"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
               step="any"               name="course"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
               step="any"               name="section_id"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
                              name="title"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
                              name="description"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
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
               step="any"               name="sort_order"                data-endpoint="PUTadmin-courses--course--sections--section_id-"
               value="2"
               data-component="body">
    <br>
<p>Optional ordering index. Example: <code>2</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course--sections--section_id-">DELETE admin/courses/{course}/sections/{section_id}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course--sections--section_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/sections/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1"
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

<span id="example-responses-DELETEadmin-courses--course--sections--section_id-">
</span>
<span id="execution-results-DELETEadmin-courses--course--sections--section_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course--sections--section_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course--sections--section_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course--sections--section_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course--sections--section_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course--sections--section_id-" data-method="DELETE"
      data-path="admin/courses/{course}/sections/{section_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course--sections--section_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course--sections--section_id-"
                    onclick="tryItOut('DELETEadmin-courses--course--sections--section_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course--sections--section_id-"
                    onclick="cancelTryOut('DELETEadmin-courses--course--sections--section_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course--sections--section_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course}/sections/{section_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course--sections--section_id-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course--sections--section_id-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course--sections--section_id-"
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
               step="any"               name="course"                data-endpoint="DELETEadmin-courses--course--sections--section_id-"
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
               step="any"               name="section_id"                data-endpoint="DELETEadmin-courses--course--sections--section_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course--sections--section_id--restore">POST admin/courses/{course}/sections/{section_id}/restore</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course--sections--section_id--restore">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/1/restore" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/restore"
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

<span id="example-responses-POSTadmin-courses--course--sections--section_id--restore">
</span>
<span id="execution-results-POSTadmin-courses--course--sections--section_id--restore" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course--sections--section_id--restore"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course--sections--section_id--restore"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course--sections--section_id--restore" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course--sections--section_id--restore">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course--sections--section_id--restore" data-method="POST"
      data-path="admin/courses/{course}/sections/{section_id}/restore"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course--sections--section_id--restore', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course--sections--section_id--restore"
                    onclick="tryItOut('POSTadmin-courses--course--sections--section_id--restore');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course--sections--section_id--restore"
                    onclick="cancelTryOut('POSTadmin-courses--course--sections--section_id--restore');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course--sections--section_id--restore"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course}/sections/{section_id}/restore</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course--sections--section_id--restore"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course--sections--section_id--restore"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course--sections--section_id--restore"
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
               step="any"               name="course"                data-endpoint="POSTadmin-courses--course--sections--section_id--restore"
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
               step="any"               name="section_id"                data-endpoint="POSTadmin-courses--course--sections--section_id--restore"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-PATCHadmin-courses--course_id--sections--section_id--visibility">PATCH admin/courses/{course_id}/sections/{section_id}/visibility</h2>

<p>
</p>



<span id="example-requests-PATCHadmin-courses--course_id--sections--section_id--visibility">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PATCH \
    "http://xyz-lms.test/admin/courses/1/sections/1/visibility" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/visibility"
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

<span id="example-responses-PATCHadmin-courses--course_id--sections--section_id--visibility">
</span>
<span id="execution-results-PATCHadmin-courses--course_id--sections--section_id--visibility" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PATCHadmin-courses--course_id--sections--section_id--visibility"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PATCHadmin-courses--course_id--sections--section_id--visibility"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PATCHadmin-courses--course_id--sections--section_id--visibility" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PATCHadmin-courses--course_id--sections--section_id--visibility">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PATCHadmin-courses--course_id--sections--section_id--visibility" data-method="PATCH"
      data-path="admin/courses/{course_id}/sections/{section_id}/visibility"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PATCHadmin-courses--course_id--sections--section_id--visibility', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PATCHadmin-courses--course_id--sections--section_id--visibility"
                    onclick="tryItOut('PATCHadmin-courses--course_id--sections--section_id--visibility');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PATCHadmin-courses--course_id--sections--section_id--visibility"
                    onclick="cancelTryOut('PATCHadmin-courses--course_id--sections--section_id--visibility');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PATCHadmin-courses--course_id--sections--section_id--visibility"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-purple">PATCH</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/visibility</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PATCHadmin-courses--course_id--sections--section_id--visibility"
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
                              name="Accept"                data-endpoint="PATCHadmin-courses--course_id--sections--section_id--visibility"
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
                              name="X-Locale"                data-endpoint="PATCHadmin-courses--course_id--sections--section_id--visibility"
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
               step="any"               name="course_id"                data-endpoint="PATCHadmin-courses--course_id--sections--section_id--visibility"
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
               step="any"               name="section_id"                data-endpoint="PATCHadmin-courses--course_id--sections--section_id--visibility"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course--sections-structure">POST admin/courses/{course}/sections/structure</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course--sections-structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"course_id\": 1,
    \"title\": \"Introduction\",
    \"description\": \"Overview of the course.\",
    \"sort_order\": 1,
    \"videos\": [
        16
    ],
    \"pdfs\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/structure"
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
        16
    ],
    "pdfs": [
        16
    ]
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTadmin-courses--course--sections-structure">
</span>
<span id="execution-results-POSTadmin-courses--course--sections-structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course--sections-structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course--sections-structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course--sections-structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course--sections-structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course--sections-structure" data-method="POST"
      data-path="admin/courses/{course}/sections/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course--sections-structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course--sections-structure"
                    onclick="tryItOut('POSTadmin-courses--course--sections-structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course--sections-structure"
                    onclick="cancelTryOut('POSTadmin-courses--course--sections-structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course--sections-structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course}/sections/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
               step="any"               name="course"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
                              name="title"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
                              name="description"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
               step="any"               name="sort_order"                data-endpoint="POSTadmin-courses--course--sections-structure"
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
               step="any"               name="videos[0]"                data-endpoint="POSTadmin-courses--course--sections-structure"
               data-component="body">
        <input type="number" style="display: none"
               name="videos[1]"                data-endpoint="POSTadmin-courses--course--sections-structure"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the videos table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdfs</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdfs[0]"                data-endpoint="POSTadmin-courses--course--sections-structure"
               data-component="body">
        <input type="number" style="display: none"
               name="pdfs[1]"                data-endpoint="POSTadmin-courses--course--sections-structure"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the pdfs table.</p>
        </div>
        </form>

                    <h2 id="endpoints-PUTadmin-courses--course--sections--section_id--structure">PUT admin/courses/{course}/sections/{section_id}/structure</h2>

<p>
</p>



<span id="example-requests-PUTadmin-courses--course--sections--section_id--structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request PUT \
    "http://xyz-lms.test/admin/courses/1/sections/1/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"title\": \"Updated Section\",
    \"description\": \"Updated description.\",
    \"sort_order\": 2,
    \"videos\": [
        16
    ],
    \"pdfs\": [
        16
    ]
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/structure"
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
        16
    ],
    "pdfs": [
        16
    ]
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-PUTadmin-courses--course--sections--section_id--structure">
</span>
<span id="execution-results-PUTadmin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-PUTadmin-courses--course--sections--section_id--structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-PUTadmin-courses--course--sections--section_id--structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-PUTadmin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-PUTadmin-courses--course--sections--section_id--structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-PUTadmin-courses--course--sections--section_id--structure" data-method="PUT"
      data-path="admin/courses/{course}/sections/{section_id}/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('PUTadmin-courses--course--sections--section_id--structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-PUTadmin-courses--course--sections--section_id--structure"
                    onclick="tryItOut('PUTadmin-courses--course--sections--section_id--structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-PUTadmin-courses--course--sections--section_id--structure"
                    onclick="cancelTryOut('PUTadmin-courses--course--sections--section_id--structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-PUTadmin-courses--course--sections--section_id--structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-darkblue">PUT</small>
            <b><code>admin/courses/{course}/sections/{section_id}/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
                              name="Accept"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
                              name="X-Locale"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="course"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="section_id"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
                              name="title"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
                              name="description"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="sort_order"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="videos[0]"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
               data-component="body">
        <input type="number" style="display: none"
               name="videos[1]"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the videos table.</p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>pdfs</code></b>&nbsp;&nbsp;
<small>integer[]</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="pdfs[0]"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
               data-component="body">
        <input type="number" style="display: none"
               name="pdfs[1]"                data-endpoint="PUTadmin-courses--course--sections--section_id--structure"
               data-component="body">
    <br>
<p>The <code>id</code> of an existing record in the pdfs table.</p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course--sections--section_id--structure">DELETE admin/courses/{course}/sections/{section_id}/structure</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course--sections--section_id--structure">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/sections/1/structure" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/structure"
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

<span id="example-responses-DELETEadmin-courses--course--sections--section_id--structure">
</span>
<span id="execution-results-DELETEadmin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course--sections--section_id--structure"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course--sections--section_id--structure"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course--sections--section_id--structure" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course--sections--section_id--structure">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course--sections--section_id--structure" data-method="DELETE"
      data-path="admin/courses/{course}/sections/{section_id}/structure"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course--sections--section_id--structure', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course--sections--section_id--structure"
                    onclick="tryItOut('DELETEadmin-courses--course--sections--section_id--structure');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course--sections--section_id--structure"
                    onclick="cancelTryOut('DELETEadmin-courses--course--sections--section_id--structure');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course--sections--section_id--structure"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course}/sections/{section_id}/structure</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course--sections--section_id--structure"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course--sections--section_id--structure"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="course"                data-endpoint="DELETEadmin-courses--course--sections--section_id--structure"
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
               step="any"               name="section_id"                data-endpoint="DELETEadmin-courses--course--sections--section_id--structure"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections--section_id--videos">GET admin/courses/{course_id}/sections/{section_id}/videos</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections--section_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/videos"
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

<span id="example-responses-GETadmin-courses--course_id--sections--section_id--videos">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections--section_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections--section_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections--section_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections--section_id--videos" data-method="GET"
      data-path="admin/courses/{course_id}/sections/{section_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections--section_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections--section_id--videos"
                    onclick="tryItOut('GETadmin-courses--course_id--sections--section_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections--section_id--videos"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections--section_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections--section_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos"
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
               step="any"               name="section_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections--section_id--videos--video_id-">GET admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections--section_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/videos/1"
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

<span id="example-responses-GETadmin-courses--course_id--sections--section_id--videos--video_id-">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections--section_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections--section_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections--section_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections--section_id--videos--video_id-" data-method="GET"
      data-path="admin/courses/{course_id}/sections/{section_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections--section_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="tryItOut('GETadmin-courses--course_id--sections--section_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections--section_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections--section_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="section_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="video_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--videos--video_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--sections--section_id--videos">POST admin/courses/{course_id}/sections/{section_id}/videos</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--sections--section_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/1/videos" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"video_id\": 10
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/videos"
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

<span id="example-responses-POSTadmin-courses--course_id--sections--section_id--videos">
</span>
<span id="execution-results-POSTadmin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--sections--section_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--sections--section_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--sections--section_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--sections--section_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--sections--section_id--videos" data-method="POST"
      data-path="admin/courses/{course_id}/sections/{section_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--sections--section_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--sections--section_id--videos"
                    onclick="tryItOut('POSTadmin-courses--course_id--sections--section_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--sections--section_id--videos"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--sections--section_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--sections--section_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
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
               step="any"               name="section_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
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
               step="any"               name="video_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--videos"
               value="10"
               data-component="body">
    <br>
<p>Video ID to attach to the section. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">DELETE admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/sections/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"video_id\": 10
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/videos/1"
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

<span id="example-responses-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">
</span>
<span id="execution-results-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-" data-method="DELETE"
      data-path="admin/courses/{course_id}/sections/{section_id}/videos/{video_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course_id--sections--section_id--videos--video_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="tryItOut('DELETEadmin-courses--course_id--sections--section_id--videos--video_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
                    onclick="cancelTryOut('DELETEadmin-courses--course_id--sections--section_id--videos--video_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/videos/{video_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="course_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="section_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="video_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
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
               step="any"               name="video_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--videos--video_id-"
               value="10"
               data-component="body">
    <br>
<p>Video ID to detach from the section. The <code>id</code> of an existing record in the videos table. Example: <code>10</code></p>
        </div>
        </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections--section_id--pdfs">GET admin/courses/{course_id}/sections/{section_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections--section_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs"
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

<span id="example-responses-GETadmin-courses--course_id--sections--section_id--pdfs">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections--section_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections--section_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections--section_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections--section_id--pdfs" data-method="GET"
      data-path="admin/courses/{course_id}/sections/{section_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections--section_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections--section_id--pdfs"
                    onclick="tryItOut('GETadmin-courses--course_id--sections--section_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections--section_id--pdfs"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections--section_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections--section_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs"
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
               step="any"               name="section_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">GET admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://xyz-lms.test/admin/courses/1/sections/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs/1"
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

<span id="example-responses-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
    </span>
<span id="execution-results-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" data-method="GET"
      data-path="admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="tryItOut('GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
                              name="Accept"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
                              name="X-Locale"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="course_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="section_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="pdf_id"                data-endpoint="GETadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the pdf. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--sections--section_id--pdfs">POST admin/courses/{course_id}/sections/{section_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--sections--section_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"pdf_id\": 7
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs"
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

<span id="example-responses-POSTadmin-courses--course_id--sections--section_id--pdfs">
</span>
<span id="execution-results-POSTadmin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--sections--section_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--sections--section_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--sections--section_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--sections--section_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--sections--section_id--pdfs" data-method="POST"
      data-path="admin/courses/{course_id}/sections/{section_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--sections--section_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--sections--section_id--pdfs"
                    onclick="tryItOut('POSTadmin-courses--course_id--sections--section_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--sections--section_id--pdfs"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--sections--section_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--sections--section_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
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
               step="any"               name="section_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
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
               step="any"               name="pdf_id"                data-endpoint="POSTadmin-courses--course_id--sections--section_id--pdfs"
               value="7"
               data-component="body">
    <br>
<p>PDF ID to attach to the section. The <code>id</code> of an existing record in the pdfs table. Example: <code>7</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">DELETE admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en" \
    --data "{
    \"pdf_id\": 7
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/pdfs/1"
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

<span id="example-responses-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">
</span>
<span id="execution-results-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-" data-method="DELETE"
      data-path="admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="tryItOut('DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    onclick="cancelTryOut('DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course_id}/sections/{section_id}/pdfs/{pdf_id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="course_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="section_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="pdf_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
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
               step="any"               name="pdf_id"                data-endpoint="DELETEadmin-courses--course_id--sections--section_id--pdfs--pdf_id-"
               value="7"
               data-component="body">
    <br>
<p>PDF ID to detach from the section. The <code>id</code> of an existing record in the pdfs table. Example: <code>7</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTadmin-courses--course--sections--section_id--publish">POST admin/courses/{course}/sections/{section_id}/publish</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course--sections--section_id--publish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/1/publish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/publish"
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

<span id="example-responses-POSTadmin-courses--course--sections--section_id--publish">
</span>
<span id="execution-results-POSTadmin-courses--course--sections--section_id--publish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course--sections--section_id--publish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course--sections--section_id--publish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course--sections--section_id--publish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course--sections--section_id--publish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course--sections--section_id--publish" data-method="POST"
      data-path="admin/courses/{course}/sections/{section_id}/publish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course--sections--section_id--publish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course--sections--section_id--publish"
                    onclick="tryItOut('POSTadmin-courses--course--sections--section_id--publish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course--sections--section_id--publish"
                    onclick="cancelTryOut('POSTadmin-courses--course--sections--section_id--publish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course--sections--section_id--publish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course}/sections/{section_id}/publish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course--sections--section_id--publish"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course--sections--section_id--publish"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course--sections--section_id--publish"
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
               step="any"               name="course"                data-endpoint="POSTadmin-courses--course--sections--section_id--publish"
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
               step="any"               name="section_id"                data-endpoint="POSTadmin-courses--course--sections--section_id--publish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course--sections--section_id--unpublish">POST admin/courses/{course}/sections/{section_id}/unpublish</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course--sections--section_id--unpublish">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/sections/1/unpublish" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/sections/1/unpublish"
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

<span id="example-responses-POSTadmin-courses--course--sections--section_id--unpublish">
</span>
<span id="execution-results-POSTadmin-courses--course--sections--section_id--unpublish" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course--sections--section_id--unpublish"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course--sections--section_id--unpublish"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course--sections--section_id--unpublish" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course--sections--section_id--unpublish">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course--sections--section_id--unpublish" data-method="POST"
      data-path="admin/courses/{course}/sections/{section_id}/unpublish"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course--sections--section_id--unpublish', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course--sections--section_id--unpublish"
                    onclick="tryItOut('POSTadmin-courses--course--sections--section_id--unpublish');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course--sections--section_id--unpublish"
                    onclick="cancelTryOut('POSTadmin-courses--course--sections--section_id--unpublish');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course--sections--section_id--unpublish"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course}/sections/{section_id}/unpublish</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course--sections--section_id--unpublish"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course--sections--section_id--unpublish"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course--sections--section_id--unpublish"
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
               step="any"               name="course"                data-endpoint="POSTadmin-courses--course--sections--section_id--unpublish"
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
               step="any"               name="section_id"                data-endpoint="POSTadmin-courses--course--sections--section_id--unpublish"
               value="1"
               data-component="url">
    <br>
<p>The ID of the section. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--videos">POST admin/courses/{course_id}/videos</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--videos">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/videos" \
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
    "http://xyz-lms.test/admin/courses/1/videos"
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

<span id="example-responses-POSTadmin-courses--course_id--videos">
</span>
<span id="execution-results-POSTadmin-courses--course_id--videos" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--videos"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--videos"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--videos" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--videos">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--videos" data-method="POST"
      data-path="admin/courses/{course_id}/videos"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--videos', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--videos"
                    onclick="tryItOut('POSTadmin-courses--course_id--videos');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--videos"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--videos');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--videos"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/videos</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--videos"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--videos"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--videos"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--videos"
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
               step="any"               name="video_id"                data-endpoint="POSTadmin-courses--course_id--videos"
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
               step="any"               name="order_index"                data-endpoint="POSTadmin-courses--course_id--videos"
               value="1"
               data-component="body">
    <br>
<p>Optional position in the course. Must be at least 0. Example: <code>1</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course_id--videos--video-">DELETE admin/courses/{course_id}/videos/{video}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course_id--videos--video-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/videos/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/videos/1"
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

<span id="example-responses-DELETEadmin-courses--course_id--videos--video-">
</span>
<span id="execution-results-DELETEadmin-courses--course_id--videos--video-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course_id--videos--video-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course_id--videos--video-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course_id--videos--video-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course_id--videos--video-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course_id--videos--video-" data-method="DELETE"
      data-path="admin/courses/{course_id}/videos/{video}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course_id--videos--video-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course_id--videos--video-"
                    onclick="tryItOut('DELETEadmin-courses--course_id--videos--video-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course_id--videos--video-"
                    onclick="cancelTryOut('DELETEadmin-courses--course_id--videos--video-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course_id--videos--video-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course_id}/videos/{video}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course_id--videos--video-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course_id--videos--video-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course_id--videos--video-"
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
               step="any"               name="course_id"                data-endpoint="DELETEadmin-courses--course_id--videos--video-"
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
               step="any"               name="video"                data-endpoint="DELETEadmin-courses--course_id--videos--video-"
               value="1"
               data-component="url">
    <br>
<p>The video. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTadmin-courses--course_id--pdfs">POST admin/courses/{course_id}/pdfs</h2>

<p>
</p>



<span id="example-requests-POSTadmin-courses--course_id--pdfs">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://xyz-lms.test/admin/courses/1/pdfs" \
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
    "http://xyz-lms.test/admin/courses/1/pdfs"
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

<span id="example-responses-POSTadmin-courses--course_id--pdfs">
</span>
<span id="execution-results-POSTadmin-courses--course_id--pdfs" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTadmin-courses--course_id--pdfs"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTadmin-courses--course_id--pdfs"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTadmin-courses--course_id--pdfs" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTadmin-courses--course_id--pdfs">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTadmin-courses--course_id--pdfs" data-method="POST"
      data-path="admin/courses/{course_id}/pdfs"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTadmin-courses--course_id--pdfs', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTadmin-courses--course_id--pdfs"
                    onclick="tryItOut('POSTadmin-courses--course_id--pdfs');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTadmin-courses--course_id--pdfs"
                    onclick="cancelTryOut('POSTadmin-courses--course_id--pdfs');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTadmin-courses--course_id--pdfs"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>admin/courses/{course_id}/pdfs</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTadmin-courses--course_id--pdfs"
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
                              name="Accept"                data-endpoint="POSTadmin-courses--course_id--pdfs"
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
                              name="X-Locale"                data-endpoint="POSTadmin-courses--course_id--pdfs"
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
               step="any"               name="course_id"                data-endpoint="POSTadmin-courses--course_id--pdfs"
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
               step="any"               name="pdf_id"                data-endpoint="POSTadmin-courses--course_id--pdfs"
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
               step="any"               name="order_index"                data-endpoint="POSTadmin-courses--course_id--pdfs"
               value="2"
               data-component="body">
    <br>
<p>Optional position in the course. Must be at least 0. Example: <code>2</code></p>
        </div>
        </form>

                    <h2 id="endpoints-DELETEadmin-courses--course_id--pdfs--pdf-">DELETE admin/courses/{course_id}/pdfs/{pdf}</h2>

<p>
</p>



<span id="example-requests-DELETEadmin-courses--course_id--pdfs--pdf-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request DELETE \
    "http://xyz-lms.test/admin/courses/1/pdfs/1" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --header "X-Locale: en"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://xyz-lms.test/admin/courses/1/pdfs/1"
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

<span id="example-responses-DELETEadmin-courses--course_id--pdfs--pdf-">
</span>
<span id="execution-results-DELETEadmin-courses--course_id--pdfs--pdf-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-DELETEadmin-courses--course_id--pdfs--pdf-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-DELETEadmin-courses--course_id--pdfs--pdf-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-DELETEadmin-courses--course_id--pdfs--pdf-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-DELETEadmin-courses--course_id--pdfs--pdf-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-DELETEadmin-courses--course_id--pdfs--pdf-" data-method="DELETE"
      data-path="admin/courses/{course_id}/pdfs/{pdf}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('DELETEadmin-courses--course_id--pdfs--pdf-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-DELETEadmin-courses--course_id--pdfs--pdf-"
                    onclick="tryItOut('DELETEadmin-courses--course_id--pdfs--pdf-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-DELETEadmin-courses--course_id--pdfs--pdf-"
                    onclick="cancelTryOut('DELETEadmin-courses--course_id--pdfs--pdf-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-DELETEadmin-courses--course_id--pdfs--pdf-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-red">DELETE</small>
            <b><code>admin/courses/{course_id}/pdfs/{pdf}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="DELETEadmin-courses--course_id--pdfs--pdf-"
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
                              name="Accept"                data-endpoint="DELETEadmin-courses--course_id--pdfs--pdf-"
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
                              name="X-Locale"                data-endpoint="DELETEadmin-courses--course_id--pdfs--pdf-"
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
               step="any"               name="course_id"                data-endpoint="DELETEadmin-courses--course_id--pdfs--pdf-"
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
               step="any"               name="pdf"                data-endpoint="DELETEadmin-courses--course_id--pdfs--pdf-"
               value="1"
               data-component="url">
    <br>
<p>The pdf. Example: <code>1</code></p>
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
