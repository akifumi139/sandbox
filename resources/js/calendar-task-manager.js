export function calendarTaskManager(options) {
  return {
    drag: null,
    suppressClick: false,
    startGantt(event, taskId, mode) {
      if (event.button !== 0 || (mode === 'move' && !event.currentTarget.querySelector('[data-resize-edge]'))) {
        return;
      }

      event.preventDefault();
      this.beginDrag(event, {
        taskId,
        mode,
        source: 'gantt',
        element: event.currentTarget,
        left: parseFloat(event.currentTarget.style.left),
        width: parseFloat(event.currentTarget.style.width),
      });
    },
    startCalendar(event, taskId, isOwner) {
      if (!isOwner || event.button !== 0) {
        return;
      }

      event.preventDefault();
      this.beginDrag(event, {
        taskId,
        source: 'calendar',
        element: event.currentTarget,
        startDate: this.calendarDateAt(event.clientX, event.clientY),
      });
    },
    beginDrag(event, details) {
      const drag = {
        ...details,
        startX: event.clientX,
        startY: event.clientY,
        moved: false,
      };

      drag.onMove = moveEvent => {
        const deltaX = moveEvent.clientX - drag.startX;
        const deltaY = moveEvent.clientY - drag.startY;
        drag.moved ||= Math.hypot(deltaX, deltaY) > 4;
        drag.element.style.transform = `translate(${deltaX}px, ${drag.source === 'calendar' ? deltaY : 0}px)`;
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
      if (drag.source === 'gantt') {
        this.applyGanttDrop(drag, event.clientX);
      }
      if (drag.source === 'calendar' && drag.moved) {
        this.applyCalendarDrop(drag, event);
      }
      drag.element.style.transform = drag.moved && drag.source === 'calendar' ? drag.element.style.transform : '';
      drag.element.style.zIndex = '';
      drag.element.style.opacity = '';
      this.drag = null;

      if (!drag.moved) {
        return;
      }

      this.suppressClick = true;
      setTimeout(() => this.suppressClick = false, 0);
      if (drag.source === 'gantt') {
        const targetDate = this.ganttDateAt(event.clientX);
        if (drag.mode === 'move') {
          const days = Math.round((event.clientX - drag.startX) / options.ganttCellWidth);
          if (days !== 0) {
            this.saveInBackground(this.$wire.moveTaskByDays(drag.taskId, days));
          }
        } else if (targetDate) {
          this.saveInBackground(this.$wire.resizeTask(drag.taskId, drag.mode, targetDate));
        }
        return;
      }

      const targetDate = this.calendarDateAt(event.clientX, event.clientY);
      if (drag.startDate && targetDate) {
        const days = Math.round((Date.parse(`${targetDate}T12:00:00Z`) - Date.parse(`${drag.startDate}T12:00:00Z`)) / 86400000);
        if (days !== 0) {
          this.saveInBackground(this.$wire.moveTaskByDays(drag.taskId, days));
        }
      }
    },
    saveInBackground(request) {
      request.then(() => this.$wire.$refresh()).catch(() => this.$wire.$refresh());
    },
    applyGanttDrop(drag, clientX) {
      if (!drag.moved) {
        return;
      }

      const targetIndex = this.ganttDayIndexAt(clientX);
      if (drag.mode === 'move') {
        const days = Math.round((clientX - drag.startX) / options.ganttCellWidth);
        drag.element.style.left = `${drag.left + (days * options.ganttCellWidth)}px`;
        return;
      }
      if (targetIndex === null) {
        return;
      }

      const targetLeft = targetIndex * options.ganttCellWidth;
      if (drag.mode === 'left' && targetLeft <= drag.left + drag.width - options.ganttCellWidth) {
        drag.element.style.left = `${targetLeft}px`;
        drag.element.style.width = `${drag.left + drag.width - targetLeft}px`;
      }
      if (drag.mode === 'right' && targetLeft >= drag.left) {
        drag.element.style.width = `${targetLeft + options.ganttCellWidth - drag.left}px`;
      }
    },
    applyCalendarDrop(drag, event) {
      const targetDate = this.calendarDateAt(event.clientX, event.clientY);
      if (!drag.startDate || !targetDate) {
        return;
      }

      const days = Math.round((Date.parse(`${targetDate}T12:00:00Z`) - Date.parse(`${drag.startDate}T12:00:00Z`)) / 86400000);
      if (days === 0) {
        drag.element.style.transform = '';
        return;
      }

      const startCell = this.calendarCellFor(drag.startDate);
      const targetCell = this.calendarCellFor(targetDate);
      if (!startCell || !targetCell) {
        return;
      }

      const startRect = startCell.getBoundingClientRect();
      const targetRect = targetCell.getBoundingClientRect();
      drag.element.style.transform = `translate(${targetRect.left - startRect.left}px, ${targetRect.top - startRect.top}px)`;
    },
    ganttDateAt(clientX) {
      const index = this.ganttDayIndexAt(clientX);
      const cells = this.$refs.ganttGrid.querySelectorAll('[wire\\:key^="gantt-day-"]');
      return index === null ? null : cells[index]?.dataset.date ?? null;
    },
    ganttDayIndexAt(clientX) {
      const rect = this.$refs.ganttGrid.getBoundingClientRect();
      const index = Math.floor((clientX - rect.left) / options.ganttCellWidth);
      const cells = this.$refs.ganttGrid.querySelectorAll('[wire\\:key^="gantt-day-"]');
      return cells.length === 0 ? null : Math.max(0, Math.min(cells.length - 1, index));
    },
    calendarDateAt(clientX, clientY) {
      const cells = this.$refs.calendarGrid.querySelectorAll('[data-date]');
      return [...cells].find(cell => {
        const rect = cell.getBoundingClientRect();
        return clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom;
      })?.dataset.date ?? null;
    },
    calendarCellFor(date) {
      return [...this.$refs.calendarGrid.querySelectorAll('[data-date]')].find(cell => cell.dataset.date === date) ?? null;
    },
    ignoreClick() {
      return this.suppressClick;
    },
  };
}

window.calendarTaskManager = calendarTaskManager;
