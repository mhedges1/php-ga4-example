<?php

/**
 * Creates a JWT (JSON Web Token) for Google OAuth 2.0 service account authentication.
 * 
 * @param array $credentials Service account credentials loaded from the JSON file.
 * @return string The signed JWT.
 */
function create_jwt($credentials) {
  // Header for the JWT, specifying the algorithm (RS256) and token type (JWT)
  $header = base64url_encode(json_encode([
      'alg' => 'RS256',
      'typ' => 'JWT'
  ]));

  // Prepare the payload, including necessary claims for Google OAuth 2.0
  $now = time();
  $expiry = $now + 3600; // Token expires after 1 hour (3600 seconds)

  $payload = base64url_encode(json_encode([
      'iss' => $credentials['client_email'], // Issuer, the service account email
      'sub' => $credentials['client_email'], // Subject, also the service account email
      'aud' => 'https://oauth2.googleapis.com/token', // Audience, Google's OAuth 2.0 token URL
      'iat' => $now, // Issued At time (current time)
      'exp' => $expiry, // Expiry time
      'scope' => 'https://www.googleapis.com/auth/analytics.readonly' // OAuth 2.0 scope for Analytics data
  ]));

  // Combine the header and payload into the unsigned JWT format: header.payload
  $unsigned_jwt = "$header.$payload";

  // Sign the JWT using the service account's private key (from the credentials JSON file)
  $privateKey = $credentials['private_key'];
  openssl_sign($unsigned_jwt, $signature, $privateKey, 'sha256');

  // Return the signed JWT: header.payload.signature
  return "$unsigned_jwt." . base64url_encode($signature);
}

/**
 * Encodes data in base64 URL format (URL-safe version of base64).
 * 
 * @param string $data The data to be encoded.
 * @return string URL-safe base64 encoded string.
 */
function base64url_encode($data) {
  // Replace '+' and '/' with '-' and '_', then trim any '=' padding at the end
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


/**
 * Retrieves an OAuth 2.0 access token by exchanging a JWT with Google OAuth 2.0.
 * 
 * @param array $credentials Service account credentials loaded from the JSON file.
 * @return string OAuth 2.0 access token.
 */
function get_access_token($credentials) {
  // Create the signed JWT
  $jwt = create_jwt($credentials);

  // Initialize a cURL request to the Google OAuth 2.0 token endpoint
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return the response as a string
  curl_setopt($ch, CURLOPT_POST, 1); // Use POST method to send the request
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
      'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', // Grant type for JWT authentication
      'assertion' => $jwt // The JWT we created
  ]));

  // Execute the cURL request and get the response
  $response = curl_exec($ch);
  
  // Check for any cURL errors
  if (curl_errno($ch)) {
      echo 'Error:' . curl_error($ch); // Output the error
  }
  
  curl_close($ch); // Close the cURL session

  // Decode the response, which should contain the access token, and return the token
  $data = json_decode($response, true);
  return $data['access_token'];
}

/**
 * Fetches data from the Google Analytics 4 API using the provided access token and property ID.
 * 
 * @param string $access_token OAuth 2.0 access token for authentication.
 * @param string $propertyId Google Analytics 4 property ID.
 * @return array Parsed response data from the Google Analytics 4 API.
 */
function fetch_ga4_data($access_token, $propertyId) {
  // Google Analytics Data API URL for the 'runReport' method
  $url = "https://analyticsdata.googleapis.com/v1beta/properties/$propertyId:runReport";

  // Define the report request body with dimensions, metrics, and date ranges
  $postFields = json_encode([
      'dimensions' => [
          ['name' => 'city'] // Dimension: City
      ],
      'metrics' => [
          ['name' => 'activeUsers'] // Metric: Active Users
      ],
      'dateRanges' => [
          [
              'startDate' => '2023-09-01', // Start date for the report
              'endDate' => '2023-09-30' // End date for the report
          ]
      ]
  ]);

  // Initialize a cURL request to the Google Analytics Data API
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: Bearer ' . $access_token, // Use the access token for authorization
      'Content-Type: application/json' // Set the content type to JSON
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); // Set the POST body with the report request data

  // Execute the cURL request and get the response
  $response = curl_exec($ch);
  
  // Check for any cURL errors
  if (curl_errno($ch)) {
      echo 'Error:' . curl_error($ch); // Output the error
  }
  
  curl_close($ch); // Close the cURL session

  // Decode and return the API response as an array
  return json_decode($response, true);
}


// -------------------
// Main program flow
// -------------------

// Load the service account credentials from a JSON file
$credentials = json_decode(file_get_contents('credentials.json'), true);

// Get an OAuth 2.0 access token using the service account credentials
$access_token = get_access_token($credentials);

// Define your Google Analytics 4 property ID
$propertyId = 'YOUR_GA4_PROPERTY_ID'; // Replace with your actual GA4 property ID

// Fetch data from the Google Analytics 4 API using the access token and property ID
$response = fetch_ga4_data($access_token, $propertyId);

// Output the retrieved data (city and active users) from the API response
foreach ($response['rows'] as $row) {
    echo 'City: ' . $row['dimensionValues'][0]['value'] . "\n"; // Output the city name
    echo 'Active Users: ' . $row['metricValues'][0]['value'] . "\n"; // Output the number of active users
}