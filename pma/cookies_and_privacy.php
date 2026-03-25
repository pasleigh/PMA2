<!DOCTYPE HTML>
<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js"></script>
<script>
    window.addEventListener("load", function(){
        var p;
        window.cookieconsent.initialise({
                "palette": {
                    "popup": {
                        "background": "#000"
                    },
                    "button": {
                        "background": "#f1d600"
                    }
                },
                content: {
                    "position": "top",
                    "message": "Be aware that this website uses cookies to help you navigate the site smoothly. Use of these and data within the website follows the privacy policies of the University of Leeds.",
                    "dismiss": "Got it!",
                    "link": 'More info',
                    "href": './cookies_and_privacy.php',
                    "theme": "light-top"
                }
            },
            function (popup) {
                p = popup;
            }
        );
        document.getElementById('btn-open').onclick = function (e) {
            p.open();
        };
        document.getElementById('btn-open2').onclick = function (e) {
            p.open();
        };
        function show_cookie_notice(){
            p.open();
        }
        //window.cookieconsent_options = {"message":"Be aware that this website uses cookies.","dismiss":"Got it!","learnMore":"More info","link":null,"theme":"light-bottom"};
    });
</script>
<style>
    body {
        font: normal 12px Verdana, Arial, sans-serif;
    }
    li {
        font-weight: bold;
        font-size: larger;
        padding-top: 10px;
    }
    .my-table{
        border: solid 1px #DDEEEE;
        border-collapse: collapse;
        border-spacing: 0;
        font: normal 13px Arial, sans-serif;
    }
    .my-table thead th {
        background-color: #DDEFEF;
        border: solid 1px #DDEEEE;
        color: #336B6B;
        padding: 10px;
        text-align: left;
        text-shadow: 1px 1px 1px #fff;
    }
    .my-table tbody td {
        border: solid 1px #DDEEEE;
        color: #333;
        padding: 10px;
        text-shadow: 1px 1px 1px #fff;
    }
</style>
<html>

<body>

<!--home page -->

<H1>Privacy and cookies</H1>

<h2>Privacy notice for apps.engineering.leeds.ac.uk/projects/[civil|mech|scape|its] and other future sub-sites</H2>

<H3>Date written: 20 June 2018</H3>

<div>This privacy notice has been adapted in line with the General Data Protection Regulation.
    You can manage your cookie settings here. <A href='#' id='btn-open' onclick="show_cookie_notice();"> See Cookie Notice</A>
<OL>

<li>Purpose of this Notice</LI>
This Notice tells you how the University of Leeds (University)
    will collect and use your personal data when you access this website.
    The University  is a <em>controller</em> of this personal data for the purposes of the data protection legislation.
Note that additional information will be provided where you are requested to enter personal information and this will vary between our various websites.
    (Information will be provided through the relevant privacy notice for that website).
It is important that you read this Notice together with any other privacy notice we provide on
    specific occasions when we are collecting or processing personal data about you so that you are fully aware of how and why we are using your data.
    This Notice supplements the other notices and is not intended to override them.

<li>Changes to this Notice</li>
We keep the information provided in this Notice under review. This Notice may be updated from time to time.
    If we make any substantial updates, we will draw these to your attention.
We will not use your personal data in any manner that is incompatible with the purpose for which the data
    were collected originally, unless we have obtained your consent to that additional use.

    <li>Anything you are not clear about</li>
If there is anything you are unclear about, please contact our Data Protection Officer, who shall be happy to answer any queries you may have concerning this Notice or the way in which we process your personal data.
The Data Protection Officer's contact details are provided at the end of this Notice.

    <li>Purpose of processing your data</LI>
Where we request personal data from you on our website you will be informed at that time of the purpose for which the data will be processed and how it will be processed, its legal basis for processing as well as any transfers to third parties.

    <li>Purpose of processing - automated collection of personal information</li>
When you access our web pages certain information your browser provides, including your IP address at the time,
    browser type, and potentially the address of the page you last visited, will be automatically
    recorded by the University.
    This data may be used to aid detection in the event of a security breach.
    The legal basis for this processing is necessity to pursue our legitimate interests to keep our website secure.
    The University retains this data in a form from which you may be identifiable for a period of up to one month.

    <li>Third-party access</li>
Your personal data that you have provided will not routinely be sent to third parties (unless notified - see 4. above).

    <li>Data retention</li>
The University's policies on retaining data for prospective and current students, staff and job applicants are detailed in our data retention policy. http://www.leeds.ac.uk/secretariat/documents/retention_policies.pdf
In other cases, for example, if you subscribe to news or events, any retention of data will be made clear at the point of collection.

    <li>Cookies</LI>
Manage your cookies
    <A href='#' id='btn-open2' onclick="show_cookie_notice();"> See Cookie Notice</A>
    <BR>
Cookies are small text files that are placed on your device by websites that you visit.
<BR>
    We use cookies for the following single purpose:
    <ul style="font-size: smaller">
<lI>They are essential to enable the website function</li>
    </ul>

    The following cookies are set:

    <table class="my-table">
        <thead>
        <tr>
            <th>Cookie</th><th>Purpose</th><th>Information</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>BASE_DIR</td><td>To store the root folder of the website</td><td>This cookie expires after 30 days</td>
        </tr>
        <tr>
            <td>ADMIN_DIR</td><td>To store the admin folder of the website</td><td>This cookie expires after 30 days</td>
        </tr>
        <tr>
            <td>BASE_URL</td><td>To store the root URL of the website</td><td>This cookie expires after 30 days</td>
        </tr>
        <tr>
            <td>STAFF_DIR</td><td>To store the staff folder of the website</td><td>This cookie expires after 30 days</td>
        </tr>
        <tr>
            <td>STUDENT_DIR</td><td>To store the student foloder of the website</td><td>This cookie expires after 30 days</td>
        </tr>
        <tr>
            <td>cookieconsent_dismidssed<BR>cookieconsent_status</td><td>To store cookie consent and acceptance information</td><td>These are persistent cookies that expire after 12 months.</td>
        </tr>
        <tr>
            <td>__cfdui</td><td>To store reference to cached libraries for individual users (specifically <em>jquery.dataTables</em>).</td><td>This is a persistent cookie that expire after at least 12 months.</td>
        </tr>
        <tr>
            <td>__utma<BR>__utmx</td><td>To store reference to cached libraries (specifically the dynamic datatables) for individual users. The cokkies collect data in an anonymous form.</td><td>These are persistent cookies that expire after at least 12 months. This dat is not passed on to </td>
        </tr>
        </tbody>
    </table>

<h3>Third party cookies and tracking</h3>

    No cookies are shared through to any other website.

    <h3>Manage your cookies and privacy</h3>

    All cookies used by this site are essential for it's functionality. <b>If you cannot accept the use of cookies you should not use this site</b>.
<BR>
Further information about cookies can be found on the Information Commissioner's Office website.

    <li>Your duty to inform us of changes</LI>

It is important that the personal data the University holds about you is accurate and current. Please keep us informed if the personal data you have provided to us needs to be updated.
<a href="http://www.leeds.ac.uk/info/5000/about/196/contact_us">Contact us </a>

<li>Your rights as a data subject</LI>
Under the GDPR, in certain circumstances, you have the right to:
    <UL style="font-size: smaller">
        <LI>withdraw your consent to our processing of your personal data where that is the legal basis for such processing</LI>
        <LI>access any of your personal data which we hold</LI>
        <LI>have your personal data which we hold corrected if it is inaccurate</LI>
        <LI>have any of your personal data which we hold erased</LI>
        <LI>restrict the ways in which we process your personal data</LI>
        <LI>object to our processing of your personal data</LI>
        <LI>receive a copy of any of your personal data which we hold in a structured and commonly used machine-readable format</LI>
        <LI>in certain cases not be subject to a decision based solely on automated decision making</LI>
        <LI>lodge a complaint with a supervisory authority</LI>
    </UL>

<li>Queries and complaints</li>
    If you have any queries or concerns relating to this privacy notice or the way your data is being processed through this website then please contact the
    University's Data Protection Officer, David Wardle on d.wardle@adm.leeds.ac.uk or by post to University of Leeds, 11.72 EC Stoner Building, Leeds LS2 9JT, UK.
    <BR>
The University's main telephone number is +44 (0) 113 2431751.
<BR>
The UK's regulator for the DPA and GDPR is the Information Commissioner's Office (ICO).
    <BR>
    The University is registered as a Data Controller with the ICO
<BR>
    Should you be dissatisfied with our handling of your concerns you have the right to complain to a supervisory authority.
    <BR>
    In the case of the UK this is the Information Commissioner's Office and details can be found at <A href="https://ico.org.uk"> https://ico.org.uk</A>.

</OL>       </div>

</body>
</html>
