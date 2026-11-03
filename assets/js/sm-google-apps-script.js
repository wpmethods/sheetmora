/* Deploy from Extensions > Apps Script in the destination spreadsheet.
 * Execute as yourself and allow Anyone access. Requests require the generated secret.
 * After changing fields or settings, generate again and deploy a NEW VERSION.
 * Existing tabs must have matching headers; use a new spreadsheet when changing schema.
 */

function jsonResponse(result) {
	return ContentService.createTextOutput(JSON.stringify(result))
		.setMimeType(ContentService.MimeType.JSON);
}

function safeCell(value) {
	if (value === null || value === undefined) {
		return '';
	}
	const text = String(value);
	// Untrusted customer/product text must never execute as a spreadsheet formula.
	return /^[\s\uFEFF]*[=+\-@]/.test(text) || /^[\t\r\n]/.test(text) ? "'" + text : text;
}

function writeOrder(sheet, data) {
	const keys = Object.keys(SM_CONFIG.fields);
	const headers = keys.map(key => SM_CONFIG.fields[key]).concat(['Last Updated']);
	if (sheet.getLastRow() === 0) {
		sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
		sheet.setFrozenRows(1);
	}
	const current = sheet.getRange(1, 1, 1, Math.max(sheet.getLastColumn(), headers.length)).getValues()[0];
	if (JSON.stringify(current) !== JSON.stringify(headers)) {
		throw new Error('Header mismatch. Use matching field settings or a new spreadsheet.');
	}
	const idColumn = keys.indexOf('order_id') + 1;
	let row = sheet.getLastRow() + 1;
	if (row > 2) {
		const ids = sheet.getRange(2, idColumn, row - 2, 1).getDisplayValues();
		const index = ids.findIndex(value => String(value[0]) === String(data.order_id));
		if (index !== -1) {
			row = index + 2;
		}
	}
	const values = keys.map(key => safeCell(data[key])).concat([new Date().toISOString()]);
	sheet.getRange(row, 1, 1, values.length).setNumberFormat('@').setValues([values]);
}

function doPost(e) {
	let lock;
	try {
		const request = JSON.parse(e && e.postData ? e.postData.contents : '{}');
		if (!SM_CONFIG.secret || request.token !== SM_CONFIG.secret) {
			return jsonResponse({ status: 'error', code: 'unauthorized' });
		}
		const data = request.data;
		if (!data || !/^[1-9]\d*$/.test(String(data.order_id))) {
			return jsonResponse({ status: 'error', code: 'invalid_order' });
		}
		lock = LockService.getScriptLock();
		lock.waitLock(30000);
		const spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
		const names = [...new Set(getSheetNames(data))];
		if (!names.length || names.some(name => typeof name !== 'string' || !name || name.length > 100)) {
			throw new Error('Invalid sheet name.');
		}
		names.forEach(name => {
			const sheet = spreadsheet.getSheetByName(name) || spreadsheet.insertSheet(name);
			writeOrder(sheet, data);
		});
		SpreadsheetApp.flush();
		return jsonResponse({ status: 'success', sheets: names.length });
	} catch (error) {
		// Avoid exposing order data, credentials, or spreadsheet contents in public responses.
		return jsonResponse({ status: 'error', code: 'write_failed' });
	} finally {
		if (lock && lock.hasLock()) {
			lock.releaseLock();
		}
	}
}
