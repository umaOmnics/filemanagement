# FileManagement Package – API Documentation

All endpoints are protected with:

auth:api

---

# Base Routes

/files
/folders

---

# FILES API

Base Prefix:

/files

---

## 1. Upload Files

Endpoint:

POST /files/{folder_id?}

Request Type:

multipart/form-data

Fields:

| Field        | Required |
|--------------|----------|
| files[]      | Yes      |
| folder_id    | No       |
| entity_type  | No       |
| entity_id    | Required if entity_type present |
| visibility   | private or public (optional) |

Example Response:

{
"status": "success",
"count": 1,
"files": [
{
"id": 1,
"title": "document",
"size": 204800,
"mime": "application/pdf",
"visibility": "private",
"object_key": "FileManager/files/uuid.pdf",
"path": "signed_or_public_url"
}
]
}

---

## 2. Delete File

DELETE /files/{id}

---

## 3. Mass Delete

POST /files/massDelete

---

## 4. Update Tags

POST /files/tags/{id}

---

## 5. Update File Metadata

POST /files/general/{id}

---

## 6. Download File

POST /files/download/{id}

---

## 7. Retrieve Trashed Files

GET /files/retrieve/all

---

## 8. Restore File

POST /files/restore/{id}

---

## 9. Mass Restore

POST /files/massRestore

---

## 10. Force Delete

POST /files/forceDelete/{id}

---

## 11. Mass Force Delete

POST /files/massForceDelete

---

# FOLDERS API

Base Prefix:

/folders

---

## 1. Create Folder

POST /folders/{parent_id?}

---

## 2. List Folder Contents

POST /folders/index/{folder_id?}

---

## 3. Show Folder

GET /folders/{id}

---

## 4. Update Folder

POST /folders/general/{id}

---

## 5. Update Folder Tags

POST /folders/tags/{id}

---

## 6. Delete Folder

DELETE /folders/{id}

---

## 7. Mass Delete

POST /folders/massDelete

---

## 8. Retrieve Trashed Folders

GET /folders/retrieve/all

---

## 9. Restore Folder

POST /folders/restore/{id}

---

## 10. Mass Restore

POST /folders/massRestore

---

## 11. Force Delete

POST /folders/forceDelete/{id}

---

## 12. Mass Force Delete

POST /folders/massForceDelete

---

# Notes

- All responses are JSON
- Upload supports multiple files
- Folder structure supports hierarchy
- Entity attachment supported
- Private files use signed URLs
