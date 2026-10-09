# Smart Leads – WordPress Plugin

A custom WordPress lead management plugin built using PHP, MySQL, JavaScript, AJAX, and the WordPress REST API.

## Features

* Admin dashboard with total lead count
* Create, view, edit, and delete leads
* Frontend lead submission form
* Form validation and input sanitization
* AJAX-based lead submission
* REST API endpoints for CRUD operations
* Permission checks for creating, updating, and deleting leads
* WordPress database integration

## Technologies Used

* WordPress
* PHP
* MySQL
* HTML and CSS
* JavaScript and jQuery
* WordPress AJAX
* WordPress REST API
* Postman
* XAMPP

## Installation

1. Set up a local WordPress installation.
2. Copy the plugin files into the WordPress plugins directory, preserving their folder structure.
3. Activate Smart Leads from the WordPress admin Plugins page.
4. Add the `[smart_leads_form]` shortcode to a page to display the lead submission form.

## REST API Endpoints

Base URL: `/wp-json/smart-leads/v1`

| Method | Endpoint      | Purpose                |
| ------ | ------------- | ---------------------- |
| GET    | `/leads`      | Retrieve all leads     |
| GET    | `/leads/{id}` | Retrieve a single lead |
| POST   | `/leads`      | Create a lead          |
| PUT    | `/leads/{id}` | Update a lead          |
| DELETE | `/leads/{id}` | Delete a lead          |

Creating, updating, and deleting leads require an authenticated WordPress administrator. Public GET endpoints currently expose lead information, so review access permissions before using this plugin with real customer data.

## Local Development

Developed and tested locally using WordPress, XAMPP, and Postman.

## Author

Sneha
