# Guide for Uploading Images to S3

## Automated Process (Recommended)

**Images are now uploaded directly to S3 without saving them locally.**

Use the `download-and-upload-images-to-s3.php` script which:
1. Downloads images temporarily
2. Uploads them directly to S3
3. Automatically deletes temporary files
4. Does not save images in `public/assets/`

## Manual Solution (Only if necessary)

If you need to upload images that are already saved locally, use the `upload-images-to-s3.php` script.

## Prerequisites

Make sure you have the following environment variables configured in your `.env` file:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-2
AWS_BUCKET=your_bucket_name
AWS_URL=https://s3.us-east-2.amazonaws.com/your_bucket_name
```

## Usage

### Option 1: Direct Upload to S3 (Recommended)

```bash
cd articles-migration
php download-and-upload-images-to-s3.php \
  https://bizee.com/articles/can-a-minor-own-a-business \
  can-a-minor-own-a-business
```

This script:
1. Downloads images temporarily
2. Uploads them directly to S3
3. Deletes temporary files
4. **Does NOT save images in `public/assets/`**

### Option 2: If you already have local images

If for some reason you already have images downloaded locally:

```bash
php upload-images-to-s3.php can-a-minor-own-a-business
```

This script:
1. Searches for local article images
2. Checks if they already exist in S3 before uploading
3. Uploads to S3 maintaining the same folder structure
4. Verifies that images are available in S3

## Structure in S3

Images are uploaded to S3 with the following structure:

```
s3://your-bucket/
└── articles/
    ├── featured/
    │   └── [slug].webp
    └── main-content/
        └── [slug]-[description].webp
```

## References in Article

Once images are uploaded, references in the Markdown article should use relative paths:

```yaml
featured_image: articles/featured/can-a-minor-own-a-business.webp
```

```yaml
image: articles/main-content/map-minor-business-ownership-states.webp
```

Statamic will automatically resolve these paths from S3.

## Complete Migration Process

1. **Migrate content**: Create the article `.md` file
2. **Download and upload images to S3**: `php download-and-upload-images-to-s3.php [URL] [SLUG]`
   - Images are uploaded directly to S3
   - Not saved locally
3. **Verify**: Check that images display correctly

**Note:** The `migrate-complete.php` script now automatically uses direct S3 upload.

## Troubleshooting

### Error: "S3 disk is not configured correctly"

- Verify that all AWS environment variables are configured
- Make sure credentials are valid
- Verify that the bucket exists and you have write permissions

### Error: "Error uploading"

- Check internet connectivity
- Review S3 bucket permissions
- Make sure the local file exists and is accessible

### Images don't display after uploading

- Verify that paths in the Markdown article match paths in S3
- Make sure images have public permissions in S3
- Check asset container configuration in `content/assets/assets.yaml`
