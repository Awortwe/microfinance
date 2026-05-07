# Nkwa Microfinance System

A comprehensive web-based microfinance management system for managing loans, savings (susu), customers, and collections.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-blue)
![License](https://img.shields.io/badge/license-MIT-green)

---

## 📋 Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Default Credentials](#default-credentials)
- [Folder Structure](#folder-structure)
- [User Roles](#user-roles)
- [Modules](#modules)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Support](#support)

---

## ✨ Features

### Core Features
- **Customer Management**: Add, edit, view, and search customers
- **Savings/Susu**: Create savings accounts, process deposits/withdrawals, view statements
- **Loan Management**: Create loan applications, approve/disburse loans, record repayments
- **Collection Tracking**: Daily collection recording and history
- **Reporting**: Daily reports, customer reports, performance metrics

### Admin Features
- **User Management**: Create and manage employee accounts
- **Loan Products**: Define and manage different loan products
- **Loan Approvals**: Review and approve/reject loan applications
- **Advanced Reports**: Portfolio analysis, profit & loss, audit trail
- **System Settings**: Configure company information and parameters
- **Database Backup**: Create and restore database backups

### Security
- **Role-Based Access**: Separate admin and employee dashboards
- **Password Encryption**: Bcrypt password hashing
- **CSRF Protection**: Cross-Site Request Forgery protection
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Input sanitization and output escaping
- **Brute Force Protection**: Login attempt limiting
- **Session Security**: HTTP-only cookies, session regeneration

---

## 💻 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache with mod_rewrite (or Nginx)
- **PHP Extensions**: 
  - PDO
  - PDO_MySQL
  - mbstring
  - json
  - gd (for image handling)
  - zlib (for backup compression)

---

## 🚀 Installation

### Step 1: Download/Extract Files

Extract all files to your web server directory (e.g., `htdocs/microfinance-system/`).

### Step 2: Create Database

1. Open phpMyAdmin or MySQL command line
2. Execute the SQL file located at `sql/database.sql`

```bash
# Via MySQL command line
mysql -u root -p < sql/database.sql