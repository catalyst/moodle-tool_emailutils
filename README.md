![GitHub Workflow Status (branch)](https://img.shields.io/github/workflow/status/catalyst/moodle-tool_emailutils/ci/MOODLE_39_STABLE)

# AWS SES Complaints Plugin for Moodle

This plugin is for use with the AWS SES service.


# Soon to come

The complaints list (/admin/tool/emailutils/index.php) is not implemented yet.

# Configuring AWS SES

AWS Simple Email Service (SES) creates Simple Notification Service (SNS) topics
for both bounces and complaints on each domain, a SNS topic is basically a
message channel, when you publish a message to a topic, it fans out the message
to all subscribed endpoints.

You can check the SNS topics on a domain by going into SES Management
Console, clicking on the domain name and then going into the Notification
section, the topics can also be found on the SNS console and they would be
called your-domain-ses-notifications and your-domain-ses-complaints, the idea
is to create subscriptions for the plug-in to this topics.

Before you can create the subscriptions you will need to create a username and
password on the plug-in configuration:

    https://yoursite.com/admin/category.php?category=tool_emailutils

First go into the SNS Console and click on Subscriptions and then Create
subscription, on the Topic ARN textbox you can type in the name or the ARN of
one of the topics, you will need to do one at the time.

On protocol you should choose HTTPS.

The endpoint needs to include the username and password you created earlier
separated  by “:”  and  followed by “@” and finally the plug-in endpoint,
something like:

    https://username:password@yoursite.com/admin/tool/emailutils/client.php

Optional settings are fine as default, please notice that both subscriptions,
notifications and complaints, should have the same endpoint.

Once they have been created you can find the subscriptions on the SNS console
Subscriptions section, they should automatically change status from “Pending
confirmation” to “Confirmed” after few minutes, if this doesn’t happen something
went wrong during the set up  process, the most common error is a typo on the
endpoint, subscription can not be change once created but you can always create
a new subscription with the right endpoint.
