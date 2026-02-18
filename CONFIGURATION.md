# FileManagement Package – Configuration Guide

This document explains how to configure the package properly,
including storage disks, environment variables, and migrations.

---

# 1. Installation

Install via Composer:

composer require uma/filemanagement

If migrations are included:

php artisan migrate

---

# 2. Storage Configuration (S3 Compatible)

This package requires S3-compatible object storage.

It is tested with Hetzner Object Storage.

---

## Step 1: Configure Disks

Open:

config/filesystems.php

Add the following disks inside the `disks` array:

'hetzner' => [
'driver' => 's3',
'key' => env('HETZNER_ACCESS_KEY'),
'secret' => env('HETZNER_SECRET_KEY'),
'region' => env('HETZNER_REGION'),
'bucket' => env('HETZNER_PRIVATE_BUCKET'),
'endpoint' => env('HETZNER_ENDPOINT'),
'use_path_style_endpoint' => true,
],

'hetznerPublic' => [
'driver' => 's3',
'key' => env('HETZNER_ACCESS_KEY'),
'secret' => env('HETZNER_SECRET_KEY'),
'region' => env('HETZNER_REGION'),
'bucket' => env('HETZNER_PUBLIC_BUCKET'),
'endpoint' => env('HETZNER_ENDPOINT'),
'use_path_style_endpoint' => true,
],

---

## Step 2: Add Environment Variables

Add the following to your .env file:

HETZNER_ACCESS_KEY=your_access_key
HETZNER_SECRET_KEY=your_secret_key
HETZNER_REGION=your_region
HETZNER_ENDPOINT=https://your-endpoint

HETZNER_PRIVATE_BUCKET=private-bucket-name
HETZNER_PUBLIC_BUCKET=public-bucket-name

---

# 3. Visibility Logic

The system automatically selects disk based on visibility:

| visibility | disk used       |
|------------|----------------|
| private    | hetzner        |
| public     | hetznerPublic  |

Private files:
- Stored in private bucket
- Returned with signed temporary URLs

Public files:
- Stored in public bucket
- Returned with direct public URL

---

# 4. Database Setup

Run migrations:

php artisan migrate

The following tables are required:

- files
- folders
- file_entities

---

# 5. Object Key Format

Uploaded files are stored using UUID-based object keys:

FileManager/files/{uuid}.{extension}

This ensures:
- No filename collision
- Immutable object references
- Clean S3 structure

---

# 6. Authentication Requirement

All routes use:

auth:api

Make sure your Laravel project has API authentication
configured using:

- Laravel Sanctum
- Laravel Passport
- Or any custom API guard

---

# 7. Storage Notes

- Ensure buckets exist before uploading
- Configure proper CORS rules
- For public bucket: allow public read access
- For private bucket: keep access restricted

---

# 8. Recommended Production Setup

- Use lifecycle policies for cleanup
- Monitor file sizes
- Set proper max upload limits
- Use CDN for public bucket
