# QR Code Fields

The QR Code Fields module for Drupal 9 enables site builders to easily generate
dynamic QR codes and integrate them into their content types. This module
provides field types and blocks for the following functionalities:

- Calendar Event:
  Feature: Generate QR codes for calendar events.
  Information: Encode event details such as title, location, date, and time into
  a QR code.

- Email Message:
  Feature: Generate QR codes for email messages.
  Information: Encode recipient email address, subject, and body into a QR code
  for quick email composition.

- Phone:
  Feature: Generate QR codes for phone numbers.
  Information: Encode phone numbers into QR codes for easy dialing when scanned.

- SMS:
  Feature: Generate QR codes for SMS messages.
  Information: Encode recipient phone number and message content into a QR code
  for quick text messaging.

- Text:
  Feature: Generate QR codes for plain text.
  Information: Encode any text-based information into a QR code for versatile
  use.

- URL:
  Feature: Generate QR codes for URLs.
  Information: Encode web addresses into QR codes for quick access when scanned.

- meCard:
  Feature: Generate QR codes using the meCard format.
  Information: Encode personal contact information, including name, address,
  phone number, email, etc. into a QR code.

- vCard v3:
  Feature: Generate QR codes using the vCard v3 format.
  Information: Encode contact details, including name, organization, address,
  phone number, email, etc. into a QR code.

- Wi-Fi Network Settings:
  Feature: Generate QR codes for Wi-Fi network settings.
  Information: Encode Wi-Fi network credentials (SSID, password, security type)
  into a QR code for easy connection.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/qrcode_fields)

To submit bug reports and feature suggestions, or to track changes in the
[issue queue](https://www.drupal.org/project/issues/qrcode_fields)


## Table of contents

 - Requirements
 - Installation
 - Configuration
 - Usage
 - Maintainers


## Requirements

Token module provides additional tokens not supported by core, most notably
fields, along with a user interface for browsing tokens. This module can
dynamically generate QR codes based on the specified content entity using
tokens.

- [Token](https://www.drupal.org/project/token)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. After enabling the module, go to the "Manage fields" section of your desired
   content type.
2. Add a new field and choose the appropriate QR code field type based on your
   desired functionality (Calendar Event, Email Message, Phone, SMS, Text, URL,
   meCard, vCard v3,  Wi-Fi Network Settings).
3. Configure the field settings as needed, including options for the label,
   default values, and display settings. Choose whether to render the field as
   a QR code image or provide a QR code image URL.
4. Save the configuration.


## Usage

### Field Types

Once the field is added to a content type, you can input the relevant data for
the chosen functionality, and the module will dynamically generate the QR code.

### Blocks

You can also place QR Code Field blocks in your site's regions via the block
admin page:

1. Navigate to Structure -> Block layout.
2. Find the desired region and click on "Place block."
3. Look for the "QR Code Field" block in the list.
4. Configure the block settings, including the chosen QR code functionality.
5. Save the block placement.

Now, the QR Code block will be displayed in the selected region on your site.


## Maintainers

- [sujan-shrestha](https://www.drupal.org/u/sujan-shrestha)
