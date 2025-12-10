@echo off
REM Windows batch script to run Messenger workers
REM Run this in a separate terminal window

echo Starting Qbil Hub Messenger Workers...
echo.
echo Press Ctrl+C to stop

REM Run both queues in the same process for simplicity on Windows
php bin/console messenger:consume async async_priority --time-limit=3600 -vv
