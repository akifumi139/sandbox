export function calendarTaskManager(options) {
  return {
    drag: null,
    suppressClick: false,
    startMonthDrag(event, taskId, mode) {
      const element = event.currentTarget.closest('[data-month-bar]');

      if (event.button !== 0 || !element || (mode === 'move' && !element.querySelector('[data-resize-edge]'))) {
        return;
      }

      event.preventDefault();
      this.beginDrag(event, {
        taskId,
        mode,
        source: 'month',
        element,
      });
    },
    beginDrag(event, details) {
      const drag = {
        ...details,
        startX: event.clientX,
        moved: false,
      };

      drag.onMove = moveEvent => {
        const deltaX = moveEvent.clientX - drag.startX;
        drag.moved ||= Math.abs(deltaX) > 4;
        drag.element.style.transform = `translate(${deltaX}px, 0)`;
        drag.element.style.zIndex = '20';
        drag.element.style.opacity = '0.8';
      };
      drag.onEnd = endEvent => this.finishDrag(drag, endEvent);
      this.drag = drag;
      window.addEventListener('pointermove', drag.onMove);
      window.addEventListener('pointerup', drag.onEnd, { once: true });
    },
    finishDrag(drag, event) {
      window.removeEventListener('pointermove', drag.onMove);
      drag.element.style.transform = '';
      drag.element.style.zIndex = '';
      drag.element.style.opacity = '';
      this.drag = null;

      if (!drag.moved) {
        return;
      }

      this.suppressClick = true;
      setTimeout(() => this.suppressClick = false, 0);
      if (drag.source === 'month') {
        const targetDate = this.monthDateAt(event.clientX);
        if (drag.mode === 'move') {
          const days = Math.round((event.clientX - drag.startX) / this.monthCellWidth());
          if (days !== 0) {
            this.saveInBackground(this.$wire.moveTaskByDays(drag.taskId, days));
          }
        } else if (targetDate) {
          this.saveInBackground(this.$wire.resizeTask(drag.taskId, drag.mode, targetDate));
        }
      }
    },
    saveInBackground(request) {
      request.then(() => this.$wire.$refresh()).catch(() => this.$wire.$refresh());
    },
    monthDateAt(clientX) {
      const index = this.monthDayIndexAt(clientX);
      const cells = this.$refs.monthGrid.querySelectorAll('[data-grid-day]');
      return index === null ? null : cells[index]?.dataset.date ?? null;
    },
    monthDayIndexAt(clientX) {
      const cells = this.$refs.monthGrid.querySelectorAll('[data-grid-day]');

      if (cells.length === 0) {
        return null;
      }

      const firstCell = cells[0].getBoundingClientRect();
      const index = Math.floor((clientX - firstCell.left) / firstCell.width);

      return Math.max(0, Math.min(cells.length - 1, index));
    },
    monthCellWidth() {
      return this.$refs.monthGrid.querySelector('[data-grid-day]')?.getBoundingClientRect().width ?? 42;
    },
    ignoreClick() {
      return this.suppressClick;
    },
  };
}

window.calendarTaskManager = calendarTaskManager;
