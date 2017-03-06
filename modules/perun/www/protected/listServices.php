<?php

/**
 * List all services from ELIXIR AAI which have enabled consent. Internal services do not have consent enabled.
 *
 * Returns list of service in format:
 * Name|Description|Organization name|Privacy policy URL|Code of conduct|Information URL\n
 *
 * Author: Michal Prochazka <michalp@ics.muni.cz>
 * Author: Ondrej Velisek <ondrejvelisek@gmail.com>
 *
 * TODO: Use standardized format (JSON)
 */

$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$spsMetadata = $metadataHandler->getList('saml20-sp-remote');

header('Content-Type: text/plain');

$delimiter = '|';

foreach ($spsMetadata as $entityID => $spMetadata) {
	if (array_key_exists('showOnServicesList', $spMetadata) && $spMetadata['showOnServicesList'] === true) {
		if (array_key_exists('name', $spMetadata)) {
			print $spMetadata['name']['en'];
		}
		print $delimiter;

		if (array_key_exists('description', $spMetadata)) {
			print $spMetadata['description']['en'];
		}
		print $delimiter;

		if (array_key_exists('OrganizationName', $spMetadata)) {
			print $spMetadata['OrganizationName']['en'];
		}
		print $delimiter;

		if (array_key_exists('privacypolicy', $spMetadata)) {
			print $spMetadata['privacypolicy'];
		}
		print $delimiter;

		if (array_key_exists('CoCo', $spMetadata) && $spMetadata['CoCo'] === true) {
			print 'yes';
		} else {
			print 'no';
		}
		print $delimiter;

		if (array_key_exists('InformationURL', $spMetadata)) {
			print $spMetadata['InformationURL']['en'];
		}

		print "\n";
	}
}
