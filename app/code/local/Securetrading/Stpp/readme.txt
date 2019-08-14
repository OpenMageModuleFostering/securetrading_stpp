-------------
1.1. Contents
-------------

1.1. Contents
1.2. About
2.1. Configuration
2.2. Notification
2.3. Redirect
2.4. Site Security
2.5. Notification Hash
2.6. I-frames
2.7. Expected Behaviour
3.1. Support

----------
1.2. About
----------

Extension version: 3.0.0

PHP Version: 5.3 onwards.

-------------------
2.1. Configuration
-------------------

To configure the extension you should perform the following steps:

1 - Login to your Magento administration area.
2 - Hover over 'System' and then click 'Configuration'.
3 - Select 'Payment Methods' on the left-hand menu.
4 - Click on 'SecureTrading STPP' - (the configurable fields are listed below)
5 - Configure the Secure Trading extension and click 'Save Config'.

The following extension fields can be configured:

Basic Configuration
-------------------
  Enabled : This controls if the extension is enabled or not.
  Title : This is the title of the extension when shown on the storefront.
  Description : This is the description of the extension when shown on the storefront
  Applicable Countries : This allows the extension to be enabled for ALL countries or specific countries as required.
  Specific Countires : If applicable countries is set to "specific countries" this is the list of allowed countries.
  Use Iframe : Controls if an iframe is used to display the payment page
  Iframe Height : If the iframe is enabled this controls the height of the iframe.
  Iframe Width : If the iframe is enabled this controls the width of the iframe.

Gateway Configuration
---------------------
  Site Reference : This is the site reference of your Secure Trading account.
  Use Site Security : Enable/disable the use of Site Security.
  Site Security Password : The Site Security Password used when generating the hash value.
  Use Notification Hash : Enabled/disable the use of Notification Hash
  Notification Hash Password : The Notification Hash Password used when generating the hash value.
  Parent CSS : The Parent CSS to use when displaying the payment page.
  Child CSS : The Child CSS to use when displaying the payment page.
  Parent JS : The Parent JS file to use when displaying the payment page.
  Child JS : The Child JS file to use when displaying the payment page.
  Payment Action : * Currently only "Authorize and Capture" is supported (see section 3.2 for more information). When set to Authorise & Capture settlestatus 0 is sent to Secure Trading and the payment will be scheduled for settlement in the next available run.
  Settle Due Date: The settle due date is the day that Secure Trading will schedule the payment to be included in the next available settlement run.
  Settle Status : This is the settle status that will be applied to this transaction.  This should normally be set to 0.

------------------
2.2. Notification
------------------

Notifications are responsible for updating order information in the Magento back-end after payment has been completed.

You must set-up a notification using MyST - for more information about MyST please see http://www.securetrading.com/support/document/category/myst/

To set-up a notification you should first add a filter and then a destination within MyST by performing the following steps:

1 - Login to MyST and select "Notifications".

2 - Select 'Add Filter' and then enter the following information:

FILTERS:
  Description:
     Enter a recognizable name of your choice here e.g. "success and decline transactions"

  Requests:
     AUTH (mandatory)
     THREEDQUERY (optional)
     ACCOUNTCHECK (optional)
     RISKDEC (optional)

  Payment Types:
     Select all payment types

  Error Codes:
     0 - successful transactions
     70000 - decline transactions

NOTE: Notifications can be sent for errorcodes other than 0 or 70000: e.g. for unauthenticated 3-D Secure payments.  
      If you wish to be informed of errorcodes other than 0 or 70000 please contact the Secure Trading Support team (support@securetrading.com).

3 - Click 'Save'.

4 - Select 'Add Destination' and enter the following information:

        
DESTINATIONS
  Description:
     Enter a recognizable name of your choice here e.g. "Magento notification destination"

  Notification Type:
     URL (This perform a HTTP POST to your Magento store)

  Process Notification:
     Online (A notification is sent to your store before the customer completes the transaction).

  Destination:
    <your_root_magento_install_here>/index.php/securetrading/redirect/notification

  Security Password:
     The value of this field is included in the notification security hash which can be used to verify the request has not been modified.

  Security Algorithm:
     sha256 (algorithm used for generating the notification hash)

  Fields: (select all of the following default fields)
     accounttypedescription
     billingprefixname
     billingfirstname
     billinglastname
     billingpremise
     billingstreet
     billingtown
     billingcounty
     billingpostcode
     billingcountryiso2a
     billingtelephone
     billingemail
     customerprefixname
     customerfirstname
     customerlastname
     customerpremise
     customerstreet
     customertown
     customercounty
     customerpostcode
     customercountryiso2a
     customertelephone
     customeremail
     enrolled
     errorcode
     maskedpan
     orderreference
     parenttransactionreference
     paymenttypedescription
     requesttypedescription
     securityresponseaddress
     securityresponsepostcode
     securityresponsesecuritycode
     settlestatus
     status
     transactionreference

  Custom Fields: (include the following custom fields)
     errordata
     errormessage
     order_increment_ids

5 - Click 'Save'.
6 - Back on the main Notifications screen, in the first row of the existing notifications table select the newly created filter and destination from the two blank drop-down boxes.
7 - Click 'Save'.

The notification is now enabled.

-------------
2.3. Redirect
-------------

Once a customer has successfully processed their transaction using the Secure Trading payment pages they can be redirected back to your Magento store.

Two redirects must be created by completing the redirect request form and emailing it to the Secure Trading Support team (support@securetrading.com).

The Redirect form can be downloaded here: http://www.securetrading.com/support/download/redirect-request-form

The following information should be included in the redirect form:

Redirect Form 1:
----------------
  Redirect URL:
    <your_root_magento_install_here>/index.php/securetrading/redirect/redirect

  Fields:
     orderreference

  Custom Fields:
     storeid
     order_increment_ids

  Accounttypedescription:
     ECOM

  Errorcode:
    0

  Requesttypedescription:
    AUTH

Redirect Form 2:
----------------
  Redirect URL:
    <your_root_magento_install_here>/index.php/admin/sales_order_create_securetrading/redirect

  Fields:
     None

  Accounttypedescription:
     MOTO

  Errorcode:
    0

  Requesttypedescription:
    AUTH

------------------
2.4. Site Security - (We strongly recommend enabling this feature).
------------------

Site Security will prevent malicious users from modifying sensitive payment information when they are transferred from your Magento store to the Secure Trading payment pages.

This feature can be enabled by following these steps:

1 - Login to your Magento administration area.
2 - Hover over 'System' and then click 'Configuration'.
3 - Select 'Payment Methods' on the left-hand menu.
4 - Click on 'SecureTrading STPP'.
5 - Click on 'Configure' to the right of 'SecureTrading Payment Pages'.
6 - Click on 'Configure' to the right of 'Gateway Configuration'.
7 - Set 'Use Site Security' to 'Yes'.
8 - Enter a hard to guess combination of letters and numbers into the 'Site Security Password' field.  This combination should be at least 8 characters long.
9 - Click 'save'.
10 - You must now notify the Secure Trading Support team via email (support@securetrading.com) that you have "enabled the Site Security Password Hash".  Please inform them that the Site Security fields are (in order):
     currencyiso3a
     mainamount
     sitereference
     settlestatus
     settleduedate
     orderreference
     accounttypedescription
     order_increment_ids
     PASSWORD *

* The last field, 'PASSWORD', is to be the combination of characters you entered into the 'Site Security Password'.

'Site Security' is now enabled.  Remember to never tell any other individuals your Site Security Password.  Do not store hard copies of this password anywhere.

----------------------
2.5. Notification Hash - (We strongly recommended enabling this feature).
----------------------

Enabling this feature will ensure that the information sent from Secure Trading to your Magento store has not been modified. 

See section 2.3 for more information on the notifications.

To enable the notification hash you will firstly have to enter a 'Security Password' in the 'Destination' window of the MyST Notification page (see section 2.3).
You will also have to enter the same password into the Secure Trading Magento extension configuration page.  Details on how to configure the extension were given in section 2.2.

--------------------
2.6. Iframe support
--------------------

By default the module uses iframes to redirect customers to the Payment Pages.

If you wish to disable iframes set 'Use Iframes' to 'No' in the module configuration (see section 2.1).  Also delete the text inside the 'Parent CSS' and 'Child JS' configuration options (see section 2.1).

-----------------------
2.7. Expected Behaviour
-----------------------

Order status
------------
* When a customer is transferred from the Magento store to the Secure Trading Payment Pages the Magento order status will be "Payment Pages".
* When a notification is sent from Secure Trading to the Magento store for a successful (errorcode 0) transaction the Magento order status will be "Processing".
* If Secure Trading return a suspended response for a transaction the Magento order status will be "Suspended" - these transactions should manually be reviewed and updated using the "Approve" or "Denied" buttons within Magento. They will also need to be updated using MyST.

* When a payment attempt fails at the Payment Pages the attempt will be logged in the order history that is visible to administrators.

Transaction reporting
---------------------
See the 'Sales - ST Transactions' menu link to view all ST transactions logged within Magento.  Or see the 'ST Transactions' tab when viewing a single order to see the transactions specific to just that order.

Log files
---------
A SecureTrading log file will be created.  See /var/log/securetrading.log

Exceptions will be put into /var/reports/* or /var/log/exception.log as per normal Magento behaviour.

Incorrect notification configuration is recognised as an exception and also inserted into /var/log/reports* or /var/log/exception.log.

Log files should be monitored regularly.

Notification
------------

If the customer changes any address fields on the Payment Pages the notification will update the address used for the order within Magento.

Order Cancellation
------------------

Orders must be cancelled manually when they have been abandoned in the Payment Pages.  To determine which orders have been abandoned:

1 - Head to the 'Sales' screen of the admin area.
2 - Set the 'Status' filter to 'Payment Pages'.
3 - Set the 'Purchased On' filter to two days ago.
4 - Click 'Search'.

The filtered orders can then be cancelled by doing the following:

1 - Click 'Select Visible'.
2 - Set 'Actions' to 'Cancel'.
3 - Click 'Submit'.
4 - Repeat until there are no filtered orders.  Set 'View' to '200' if you have a large number of orders.

------------
3.1. Support
------------

If you require any assistance then please contact us immediately.

When contacting our Support department you should send the contents of /var/log/ and /var/reports/ with your initial support request.

Please also send us any server access/error logs along with as much information as you can regarding your problem.

http://www.securetrading.com/contact.html
support@securetrading.com
