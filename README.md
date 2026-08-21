# TimeEdit → Moodle Integration (Attendance Web Service)

A Moodle local plugin that provides a **web service layer and scheduled processing pipeline** for managing attendance sessions.

The core functionality centres around:
- External API functions for **upsert (create/update)** and **delete** operations that store API calls in a local table.
- A **scheduled task** that processes the API calls stored in the local table.

This enables external systems (e.g. TimeEdit) to reliably synchronise attendance sessions into Moodle.

---

## What it does

### 1) Attendance session processing flow (Scheduled Task)

High-level steps performed by the scheduled task (e.g. `process_reservations`):

1. **Guard: check plugin settings**
    - Retrieves plugin configuration (e.g. via `get_settings`)
    - If disabled → processing is **SKIPPED**

2. **Fetch unprocessed records**
    - Retrieves session/reservation records that:
        - Have not yet been processed
        - Are eligible for sync
    - Typically sourced from an integration table (e.g. reservations or staging data)

3. **Iterate through records**
    - Processes each record individually or in batches
    - Extracts required data:
        - Course/module identifiers
        - Session name (e.g. set / cohort)
        - Start and end timestamps

4. **Determine operation type**
    - Based on the incoming data/state, determines whether to:
        - Create a new session
        - Update an existing session
        - Delete a session (if applicable)

5. **Delegate to session manager**
    - Uses the `session_manager` abstraction to:
        - Standardise data structure
        - Handle shared business logic
    - Ensures consistent behaviour across all entry points

6. **Execute Moodle operations**
    - Calls underlying Moodle / attendance APIs to:
        - Create sessions
        - Update sessions
        - Delete sessions
    - If a failure occurs → task is marked as **FAILED** (fail-fast)

7. **Mark record as processed**
    - Successfully handled records are marked as processed
    - Prevents duplicate processing on subsequent runs

8. **Logging and error handling**
    - Logs:
        - Successful operations
        - Failures (with context)
    - Critical errors stop execution for visibility

---

### 2) External API flow (externallib)

The plugin exposes external functions for direct interaction with attendance sessions.

#### a) Upsert sessions (`add/update combined`)

Purpose:
- Allows external systems to **create or update sessions in bulk**

Flow:

1. **Validate input parameters**
    - Ensures required fields are present and correctly formatted

2. **Normalise payload**
    - Transforms incoming data into a consistent internal structure

3. **Iterate through sessions**
    - Each session is processed individually

4. **Determine create vs update**
    - If session exists → **update**
    - If not → **create**

5. **Delegate to session manager**
    - Uses shared logic for:
        - Data mapping
        - Validation
        - Execution

6. **Return structured response**
    - Includes:
        - Success/failure per session
        - IDs of created/updated sessions
        - Error messages where applicable

---

#### b) Delete sessions

Purpose:
- Allows external systems to **remove attendance sessions**

Flow:

1. **Validate input**
    - Ensures session identifiers are provided

2. **Locate target sessions**
    - Matches incoming identifiers to Moodle session records

3. **Delete sessions**
    - Uses Moodle/attendance APIs to remove sessions

4. **Return response**
    - Indicates:
        - सफल deletions
        - Any failures (e.g. session not found)

---

## Scheduled task behavior

The scheduled task is the **primary automation mechanism** and is expected to:

- Run via Moodle cron at configured intervals
- Process pending records incrementally
- Ensure idempotent behaviour where possible
- Avoid duplicate session creation

Execution characteristics:

- Batch-oriented processing
- Fail-fast on critical errors
- Persistent state tracking (processed vs unprocessed)
- Designed for retry safety

---

## Tech stack

- PHP (Moodle plugin architecture)
- Moodle 4.x APIs
- Scheduled tasks via Moodle cron
- External functions (`externallib.php`)
- Service abstraction (`session_manager`) for shared logic

---

## Modules / dependencies

This plugin depends on:

- Moodle core APIs
- Moodle Attendance module

Ensure:

- Attendance module is installed and configured
- Cron is running correctly for scheduled task execution
- Web services are enabled for external API usage
- Proper permissions are configured for API access

---

## Manual deployment / configuration

After installing or updating the plugin:

1. Ensure the Moodle Attendance module is installed and configured.
2. Deploy the plugin code to `local/attendance_ws`.
3. Run the Moodle upgrade process.
4. Enable web services and the required protocol, e.g. REST.
5. Confirm the `Attendance web service` external service is enabled.
6. Add the required plugin functions to the external service if they are not already present.
7. Create or confirm a dedicated web service user and token for the integration.
8. Create or update a dedicated system-level API role for the token user.
9. Assign the required attendance web service capability/capabilities to that role.
10. Assign the role to the web service token user at system context.
11. Restrict the external service to the dedicated API user/token.
12. Confirm Moodle cron is running.
13. Confirm the scheduled task is enabled and its schedule is appropriate for the TimeEdit update frequency.
14. Enable the plugin in its settings, if required.

Recommended API role setup:

- Role archetype: `None`
- Context type where the role may be assigned: `System`
- Allow role assignments: none
- Allow role overrides: none
- Allow role switches: none
- Grant only the required attendance web service capability/capabilities

The token user should only be given the permissions needed for the approved web service functions.

---