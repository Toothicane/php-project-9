[![hexlet-check](https://github.com/Toothicane/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Toothicane/php-project-9/actions)  

# Page Analyzer (PHP)

Page Analyzer is a web service for analyzing web pages and collecting basic SEO metadata.  
The application accepts a URL and sends an HTTP request to the specified website. It stores the HTTP response status code and extracts the following SEO elements from the returned HTML document, if they are present:

`<h1>` content  
`<title>` content  
`<meta name="description">` content

## Usage

To add a website, enter its URL on the **Main** page and submit the form. After the URL is validated and added, it becomes available on the **Sites** page.  
Select a website from the list to view its details. The page displays the website information and the results of all previously performed checks.  
To perform another check, click **Run check**. The results are added to the check history.

## Project demo

The deployed application with a connected database is available here:
[deployed project](https://php-project-9-1upv.onrender.com)