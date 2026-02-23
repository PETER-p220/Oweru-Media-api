# Instagram API Integration Setup Guide

This guide will help you set up Instagram API integration for the Oweru Media Management System.

## Prerequisites

1. **Facebook Business Account**: You need a Facebook Business account
2. **Instagram Professional Account**: Your Instagram account must be a Business or Creator account
3. **Facebook App**: You need to create a Facebook app with Instagram Graph API permissions

## Step 1: Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or use an existing one
3. Add "Instagram Basic Display" or "Instagram Graph API" product
4. Configure your app settings:
   - App Domains: Add your domain (e.g., localhost:8000 for development)
   - Privacy Policy URL: Add your privacy policy URL
   - Contact Email: Add your contact email

## Step 2: Get Instagram Business Account ID

1. Go to [Facebook Graph API Explorer](https://developers.facebook.com/tools/explorer/)
2. Select your app and get a short-lived access token
3. Make a GET request to `me/accounts` to get your business accounts
4. Find your Instagram Business Account ID from the response

## Step 3: Configure Environment Variables

Add the following to your `.env` file:

```env
# Enable Instagram API
INSTAGRAM_ENABLED=true

# Facebook App Credentials
INSTAGRAM_APP_ID=your_facebook_app_id
INSTAGRAM_APP_SECRET=your_facebook_app_secret

# Instagram Access Token (long-lived)
INSTAGRAM_ACCESS_TOKEN=your_long_lived_access_token

# Instagram Business Account ID
INSTAGRAM_BUSINESS_ACCOUNT_ID=your_instagram_business_account_id

# API Version (optional, defaults to v18.0)
INSTAGRAM_API_VERSION=v18.0
```

## Step 4: Get Long-Lived Access Token

1. Use the short-lived token from Step 2
2. Make a POST request to:
   ```
   https://graph.facebook.com/oauth/access_token?
   grant_type=fb_exchange_token&
   client_id={APP_ID}&
   client_secret={APP_SECRET}&
   fb_exchange_token={SHORT_LIVED_TOKEN}
   ```
3. The response will contain your long-lived access token (valid for 60 days)

## Step 5: Test the Integration

Test your Instagram API connection:

```bash
# Test API status
curl -X GET "http://localhost:8000/api/instagram/status"

# Test account info
curl -X GET "http://localhost:8000/api/instagram/account"
```

## Step 6: Required Permissions

Make sure your Facebook app has these permissions:
- `instagram_basic`
- `pages_show_list`
- `instagram_content_publish`
- `business_management`

## API Endpoints

### Create Instagram Post
```
POST /api/instagram/post
Content-Type: multipart/form-data

Parameters:
- caption: string (required, max 2200 characters)
- post_type: string (required, options: feed, carousel, reel)
- post_id: integer (required)
- media: array of files (required, 1-10 files)
```

### Get Account Info
```
GET /api/instagram/account
```

### Get API Status
```
GET /api/instagram/status
```

## Limitations

- **Posts**: 25 posts per hour
- **Comments**: 60 comments per hour  
- **Likes**: 60 likes per hour
- **Media Size**: Images max 8MB, Videos max 50MB
- **Caption Length**: Max 2200 characters
- **Hashtags**: Max 30 hashtags per post

## Troubleshooting

### Common Issues

1. **"Unexpected token '<', "<!DOCTYPE "... is not valid JSON"**
   - This means the API returned HTML instead of JSON
   - Check if your Laravel server is running
   - Verify the API endpoint URL is correct

2. **"Invalid OAuth access token"**
   - Your access token has expired or is invalid
   - Get a new long-lived access token
   - Check if INSTAGRAM_ENABLED is true

3. **"Insufficient permissions"**
   - Your Facebook app doesn't have the required permissions
   - Re-authenticate your Instagram account
   - Check app permissions in Facebook Developers

4. **"Media upload failed"**
   - Check file size limits (8MB for images, 50MB for videos)
   - Verify file formats (JPG, PNG for images; MP4, MOV for videos)
   - Check if media files are accessible

### Debug Mode

Enable debug logging in your `.env`:
```env
INSTAGRAM_LOGGING_ENABLED=true
INSTAGRAM_LOG_CHANNEL=instagram
```

Check your Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

## Security Notes

- Never commit your access tokens to version control
- Use environment variables for all sensitive credentials
- Rotate your access tokens periodically
- Monitor your API usage to avoid rate limits
- Consider implementing webhook security if using webhooks

## Production Considerations

1. **SSL Certificate**: Your production domain must have a valid SSL certificate
2. **Webhooks**: Configure webhooks for real-time updates (optional)
3. **Rate Limiting**: Implement client-side rate limiting
4. **Error Handling**: Implement robust error handling for API failures
5. **Monitoring**: Monitor API usage and error rates

## Support

For more information:
- [Instagram Graph API Documentation](https://developers.facebook.com/docs/instagram-api/)
- [Facebook Developers Help Center](https://developers.facebook.com/support/)
- [Instagram Business Help Center](https://business.instagram.com/help/)
