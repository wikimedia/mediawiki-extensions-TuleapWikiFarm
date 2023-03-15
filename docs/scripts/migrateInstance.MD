## Migration script

To migrate an old instance to the new version, run script:

	{root}/extensions/TuleapWikiFarm/maintenance/migrateInstance.php --projectname={instance_name} --groupid={id}

### Params

Script can migrate instances that were already registered in the new system, or can do the registration as well.

- `skip-registration` : skip registration of the instance (default: false). If set, script will expect instance
to be already registered in the new system.
- `projectname` : name of the project (required)
- `groupid` : id of the project (required if registering)
- `dbprefix` : prefix of the database (required if central db is used and is registering)
