<?php
require_once "context/AbstractSoapConnectionProvider.php";
require_once "soapclient/SforcePartnerClient.php";

class PartnerConnectionProvider extends AbstractSoapConnectionProvider {
    function establish(ConnectionConfiguration $connConfig) {
        $connection =  new SforcePartnerClient();

        $connection->createConnection($this->buildWsdlPath($connConfig));
        $connection->setEndpoint($this->buildEndpoint($connConfig));
        $connection->setSessionHeader($connConfig->getSessionId());
        $connection->setCallOptions(new CallOptions($connConfig->getClientId(), WorkbenchConfig::get()->value('callOptions_defaultNamespace')));

        if (WorkbenchContext::get()->isApiVersionAtLeast(27.0)) {
            if (!WorkbenchConfig::get()->value('ownerChangeOptions_transferAttachments') || !WorkbenchConfig::get()->value('ownerChangeOptions_transferOpenActivities')) {
                $connection->setOwnerChangeOptionsHeader(
                    new OwnerChangeOptionsHeader(
                        WorkbenchConfig::get()->value('ownerChangeOptions_transferAttachments'),
                        WorkbenchConfig::get()->value('ownerChangeOptions_transferOpenActivities')
                    )
                );
            }
        }

        if (WorkbenchConfig::get()->value('assignmentRuleHeader_assignmentRuleId') || WorkbenchConfig::get()->value('assignmentRuleHeader_useDefaultRule')) {
            $connection->setAssignmentRuleHeader(
                new AssignmentRuleHeader(
                    WorkbenchConfig::get()->value('assignmentRuleHeader_assignmentRuleId'),
                    WorkbenchConfig::get()->value('assignmentRuleHeader_useDefaultRule')
                )
            );
        }

        if (WorkbenchConfig::get()->value('mruHeader_updateMru')) {
            $connection->setMruHeader(new MruHeader(WorkbenchConfig::get()->value('mruHeader_updateMru')));
        }

        if (WorkbenchConfig::get()->value('queryOptions_batchSize')) {
            $connection->setQueryOptions(new QueryOptions(WorkbenchConfig::get()->value('queryOptions_batchSize')));
        }

        if (WorkbenchConfig::get()->value('emailHeader_triggerAutoResponseEmail') ||
            WorkbenchConfig::get()->value('emailHeader_triggerOtherEmail') ||
            WorkbenchConfig::get()->value('emailHeader_triggertriggerUserEmail')) {

            $connection->setEmailHeader(new EmailHeader(
                    WorkbenchConfig::get()->value('emailHeader_triggerAutoResponseEmail'),
                    WorkbenchConfig::get()->value('emailHeader_triggerOtherEmail'),
                    WorkbenchConfig::get()->value('emailHeader_triggertriggerUserEmail')
                )
            );
        }

        if (WorkbenchConfig::get()->value('UserTerritoryDeleteHeader_transferToUserId')) {
            $connection->setUserTerritoryDeleteHeader(
                new UserTerritoryDeleteHeader(WorkbenchConfig::get()->value('UserTerritoryDeleteHeader_transferToUserId')));
        }

        if (WorkbenchConfig::get()->value('allowFieldTruncationHeader_allowFieldTruncation')) {
            $connection->setAllowFieldTruncationHeader(
                new AllowFieldTruncationHeader(WorkbenchConfig::get()->value('allowFieldTruncationHeader_allowFieldTruncation')));
        }

        if (WorkbenchConfig::get()->value('allOrNoneHeader_allOrNone')) {
            $connection->setAllOrNoneHeader(
			    new AllOrNoneHeader(WorkbenchConfig::get()->value('allOrNoneHeader_allOrNone')));
        }

        if (WorkbenchConfig::get()->value('disableFeedTrackingHeader_disableFeedTracking')) {
            $connection->setDisableFeedTrackingHeader(
			    new DisableFeedTrackingHeader(WorkbenchConfig::get()->value('disableFeedTrackingHeader_disableFeedTracking')));
        }

        if (WorkbenchConfig::get()->value('localOptions_language')) {
            $connection->setLocaleOptions(
			    new LocaleOptions(WorkbenchConfig::get()->value('localOptions_language')));
        }

        if (WorkbenchConfig::get()->value('packageVersionHeader_include') &&
            WorkbenchConfig::get()->value('packageVersion_namespace') &&
            WorkbenchConfig::get()->value('packageVersion_majorNumber') &&
            WorkbenchConfig::get()->value('packageVersion_minorNumber')) {
            $connection->setPackageVersionHeader(
                WorkbenchConfig::get()->value("packageVersion_namespace"),
                WorkbenchConfig::get()->value("packageVersion_majorNumber"),
                WorkbenchConfig::get()->value("packageVersion_minorNumber")
		    );
        }

        return $connection;
    }

    function getWsdlType() {
        return "partner";
    }

    function getEndpointType() {
        return "Soap/u";
    }
}

?>
