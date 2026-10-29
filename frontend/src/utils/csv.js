/**
 * QAFlow — CSV Export Utility
 *
 * Generates and triggers a CSV file download from an array of objects.
 * Used by the Reports page to export data without a server-side pipeline.
 */

/**
 * Escapes a value for CSV (handles quotes and commas).
 * @param {any} value
 * @returns {string}
 */
function escapeCsvValue(value) {
  if (value === null || value === undefined) {
    return '';
  }
  const str = String(value);
  if (str.includes(',') || str.includes('"') || str.includes('\n') || str.includes('\r')) {
    return `"${str.replace(/"/g, '""')}"`;
  }
  return str;
}

/**
 * Triggers a browser download of a CSV file.
 * @param {Array<Object>} data - Array of objects to export.
 * @param {Array<{key: string, label: string}>} columns - Column definitions mapping object keys to human-readable headers.
 * @param {string} filename - The name of the downloaded file (e.g., "report.csv").
 */
export function exportToCsv(data, columns, filename = 'export.csv') {
  if (!data || !data.length) {
    console.warn('No data to export');
    return;
  }

  // Generate header row
  const headerRow = columns.map((col) => escapeCsvValue(col.label)).join(',');

  // Generate data rows
  const dataRows = data.map((row) => {
    return columns
      .map((col) => {
        // Support nested keys like 'user.name'
        const value = col.key.split('.').reduce((obj, key) => (obj ? obj[key] : undefined), row);
        return escapeCsvValue(value);
      })
      .join(',');
  });

  // Combine and create blob
  const csvContent = [headerRow, ...dataRows].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  
  // Create a temporary link element to trigger the download
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  // Clean up the blob URL
  URL.revokeObjectURL(url);
}