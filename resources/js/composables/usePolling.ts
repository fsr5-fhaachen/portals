import { onBeforeUnmount } from "vue";

/**
 * Execute a callback periodically. The polling pauses while the browser tab is hidden and
 * runs immediately when the tab becomes visible again. Overlapping executions are skipped.
 * Must be called in the setup of a component (stops automatically on unmount).
 */
export default function (
  callback: () => Promise<void> | void,
  intervalMs: number,
) {
  let isRunning = false;

  const run = async () => {
    if (isRunning || document.hidden) {
      return;
    }

    isRunning = true;
    try {
      await callback();
    } catch (error) {
      console.error(error);
    } finally {
      isRunning = false;
    }
  };

  const interval = setInterval(run, intervalMs);

  const onVisibilityChange = () => {
    if (!document.hidden) {
      run();
    }
  };
  document.addEventListener("visibilitychange", onVisibilityChange);

  const stop = () => {
    clearInterval(interval);
    document.removeEventListener("visibilitychange", onVisibilityChange);
  };

  onBeforeUnmount(stop);

  return {
    run,
    stop,
  };
}
