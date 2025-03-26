<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS System for File Transfers and Creative Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        h1, h2 {
            color: #333;
        }

        h3 {
            color: #555;
        }

        ul {
            list-style-type: disc;
            padding-left: 20px;
        }

        pre {
            background-color: #e5e5e5;
            padding: 10px;
            border-radius: 5px;
        }

        a {
            color: #1D4ED8;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .table-of-contents {
            margin-bottom: 30px;
        }

        .tech-stack, .features, .usage, .contributing, .acknowledgements {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <h1>CMS System for File Transfers and Creative Management (Still in development)</h1>

    <p>A CMS system built with Laravel, Vue.js, Vite, and Inertia.js to manage creative assets and file transfers. This project allows users to upload, manage, and transfer various types of creatives such as HTML5 banners, videos, GIFs, and social media images. It also supports file transfers and bill management.</p>

    <h2>Project Overview</h2>
    <p>This project provides a Content Management System (CMS) that facilitates managing a variety of creative assets and files. It supports:</p>
    <ul>
        <li>Uploading and managing 4 types of creatives: HTML5 banners, videos, GIFs, and social media images.</li>
        <li>File transfers to share files with clients or teams.</li>
        <li>Bill management for generating and tracking associated invoices.</li>
    </ul>
    <p>The project is built using Laravel for the backend, Vue.js for the frontend, Vite as the bundler for faster development, and Inertia.js to integrate the backend with the frontend seamlessly.</p>

    <h2>Tech Stack</h2>
    <div class="tech-stack">
        <h3>Backend:</h3>
        <ul>
            <li>Laravel 9.x</li>
            <li>Eloquent ORM</li>
            <li>Blade Templating</li>
            <li>Authentication (JWT, Laravel Passport)</li>
            <li>File Storage (Local, S3)</li>
        </ul>
        <h3>Frontend:</h3>
        <ul>
            <li>Vue.js 3.x</li>
            <li>Vue Router</li>
            <li>Vite for Fast Rebuilds</li>
            <li>Inertia.js for Single Page Application (SPA) functionality</li>
            <li>TailwindCSS for Styling</li>
            <li>SweetAlert2 for Modal Popups</li>
        </ul>
        <h3>Database:</h3>
        <ul>
            <li>MySQL or PostgreSQL</li>
        </ul>
        <h3>File Upload:</h3>
        <ul>
            <li>File storage for HTML5 banners, videos, gifs, and images.</li>
        </ul>
        <h3>Other Libraries:</h3>
        <ul>
            <li>Axios (for HTTP requests)</li>
            <li>Lucide Vue (for icons)</li>
        </ul>
    </div>

    <h2>Features</h2>
    <div class="features">
        <ul>
            <li><strong>File Uploads:</strong> Upload HTML5 banners, videos, GIFs, and images directly through the CMS.</li>
            <li><strong>File Transfers:</strong> Easily send files to clients with built-in file transfer functionality.</li>
            <li><strong>Bill Management:</strong> Generate and track bills for uploaded files and completed transfers.</li>
            <li><strong>User Management:</strong> Allows logged-in users to manage their uploaded files and track their transfers.</li>
            <li><strong>Search & Pagination:</strong> Efficient file search with pagination support.</li>
            <li><strong>SweetAlert2 Integration:</strong> Confirm file deletions and other important actions with beautiful modals.</li>
        </ul>
    </div>

    <h2>Prerequisites</h2>
    <ul>
        <li>PHP 8.0+</li>
        <li>Node.js 16.x+</li>
        <li>Composer</li>
        <li>MySQL or PostgreSQL Database</li>
    </ul>

    <h2>Usage</h2>
    <div class="usage">
        <h3>Features</h3>
        <ul>
            <li><strong>Upload Files:</strong> Go to the File Transfers page to upload your creatives (HTML5 banners, Videos, GIFs, Social Images). You can drag and drop files, or choose from your file system.</li>
            <li><strong>Search and Filter:</strong> Use the search bar to find specific file transfers quickly.</li>
            <li><strong>File Transfers:</strong> Share files with clients through the File Transfers page.</li>
            <li><strong>Bill Management:</strong> Generate and manage bills associated with file transfers.</li>
            <li><strong>Delete Files:</strong> Easily remove files you no longer need.</li>
        </ul>
    </div>

    <h2>Acknowledgements</h2>
    <div class="acknowledgements">
        <ul>
            <li>Laravel for being an amazing framework for web development.</li>
            <li>Vue.js for making the frontend so interactive and fun to work with.</li>
            <li>Inertia.js for seamlessly bridging backend and frontend.</li>
            <li>Vite for providing lightning-fast development.</li>
            <li>TailwindCSS for a simple yet elegant design.</li>
        </ul>
    </div>

    <footer>
        <p>For any inquiries, feel free to contact me at <a href="mailto:govindaroy.ofc94@gmail.com">govindaroy.ofc94@gmail.com</a></p>
    </footer>
</body>
</html>
