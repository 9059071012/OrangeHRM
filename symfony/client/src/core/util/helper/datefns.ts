import {
  parse,
  format,
  isDate,
  compareAsc,
  startOfYear,
  endOfYear,
  getDaysInMonth,
  addDays,
  isSameDay,
  differenceInSeconds,
  differenceInCalendarDays,
} from 'date-fns';

const freshDate = () => {
  return new Date(new Date().setHours(0, 0, 0, 0));
};

const parseDate = (value: string, dateFormat: string): Date | null => {
  try {
    const parsed = parse(value, dateFormat, freshDate());
    return !isNaN(parsed.valueOf()) ? parsed : null;
  } catch (error) {
    return null;
  }
};

const formatDate = (value: Date, dateFormat: string): string | null => {
  try {
    return format(value, dateFormat);
  } catch (error) {
    return null;
  }
};

const isBefore = (
  reference: string,
  comparable: string,
  dateFormat: string,
): boolean => {
  const referenceDate = parseDate(reference, dateFormat);
  const comparableDate = parseDate(comparable, dateFormat);

  if (referenceDate && comparableDate) {
    return compareAsc(referenceDate, comparableDate) === -1 ? true : false;
  }

  return false;
};

const isAfter = (
  reference: string,
  comparable: string,
  dateFormat: string,
): boolean => {
  const referenceDate = parseDate(reference, dateFormat);
  const comparableDate = parseDate(comparable, dateFormat);

  if (referenceDate && comparableDate) {
    return compareAsc(referenceDate, comparableDate) === 1 ? true : false;
  }

  return false;
};

const isEqual = (
  reference: string,
  comparable: string,
  dateFormat: string,
): boolean => {
  const referenceDate = parseDate(reference, dateFormat);
  const comparableDate = parseDate(comparable, dateFormat);

  if (referenceDate && comparableDate) {
    return compareAsc(referenceDate, comparableDate) === 0 ? true : false;
  }

  return false;
};

const numberOfDaysInMonth = (
  month: number | undefined, // 1 - 12
  discardLeapYear: boolean,
): number => {
  if (month && month > 0 && month <= 12) {
    const days = getDaysInMonth(new Date().setMonth(month - 1));
    return discardLeapYear && days === 29 ? 28 : days;
  }

  return 0;
};

const parseTime = (value: string, timeFormat: string): Date | null => {
  return parseDate(value, timeFormat);
};

const compareTime = (
  reference: string,
  comparable: string,
  timeFormat: string,
): number => {
  const referenceTime = parseDate(reference, timeFormat);
  const comparableTime = parseDate(comparable, timeFormat);

  if (referenceTime && comparableTime) {
    console.log(referenceTime, comparableTime);
    if (referenceTime.valueOf() < comparableTime.valueOf()) {
      return 1;
    }
    if (referenceTime.valueOf() > comparableTime.valueOf()) {
      return -1;
    }
    if (referenceTime.valueOf() === comparableTime.valueOf()) {
      return 0;
    }
  }

  return NaN;
};

const diffInDays = (
  fromDate: string,
  toDate: string,
  dateFormat = 'yyyy-MM-dd',
): number => {
  const from = parseDate(fromDate, dateFormat);
  const to = parseDate(toDate, dateFormat);
  if (from && to) {
    return isSameDay(to, from) ? 1 : differenceInCalendarDays(to, from) + 1;
  }
  return 0;
};

const diffInTime = (
  startTime: string,
  endTime: string,
  timeFormat = 'hh:mm a',
): number => {
  const start = parseTime(startTime, timeFormat);
  const end = parseTime(endTime, timeFormat);
  if (start && end) {
    const diffInSecs = differenceInSeconds(end, start);
    if (diffInSecs > 0) return diffInSecs;
  }
  return 0;
};

const secondsTohhmm = (seconds: number): string => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds - hours * 3600) / 60);
  return `${hours.toString().padStart(2, '0')}.${minutes
    .toString()
    .padStart(2, '0')}`;
};

export {
  isDate,
  freshDate,
  parseDate,
  formatDate,
  isAfter,
  isBefore,
  isEqual,
  startOfYear,
  endOfYear,
  numberOfDaysInMonth,
  addDays,
  parseTime,
  diffInDays,
  diffInTime,
  secondsTohhmm,
  compareTime,
};
