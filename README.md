## Step 1: Create a Google Cloud Project
  1. Go to the Google Cloud Console.
  2. Click the Select a Project dropdown and then click New Project.
  3. Enter a name for your project and click Create.
  4. After the project is created, make sure it is selected in the dropdown.

## Step 2: Enable the Google Analytics Data API

  1. In the Google Cloud Console, go to APIs & Services > Library.
  2. Search for Google Analytics Data API.
  3. Click on it, and then click Enable to enable the API for your project.

## Step 3: Create a Service Account

  1. In the Google Cloud Console, go to APIs & Services > Credentials.
  2. Click Create Credentials and choose Service Account.
        Fill in the required fields:
        Service account name: Choose a name like "GA4 Data Access".
        Service account ID: This will be auto-generated based on the service account name.
        Description: Optionally add a description (e.g., "Service account for accessing GA4 Data API").
  3. Click Create and Continue.
  4. In the Grant this service account access to project step, you can skip this, as it’s not required for this task.
  5. In the Grant users access to this service account step, skip it by clicking Done.

## Step 4: Download the credentials.json File

   1.  After the service account is created, it will appear under the Service Accounts section.
   2. Find the newly created service account and click on the Actions button (three dots) on the right, then click Manage keys.
   3. In the Keys section, click Add Key and then choose Create New Key.
   4. Select JSON as the key type, and then click Create.
   5. A file named something like your-service-account-name-xxxxxxxx.json will be downloaded to your computer. This is your credentials.json file.

``` 
Make sure to add your client_email to the "Roles and Data Restrictions" section of google analytics under property access management