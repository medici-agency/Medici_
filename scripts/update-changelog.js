#!/usr/bin/env node
/**
 * Automatic CHANGELOG.md updater
 * Syncs completed TODO items to CHANGELOG
 *
 * Usage:
 *   node scripts/update-changelog.js
 *   node scripts/update-changelog.js --auto (no prompts)
 */

const fs = require('fs');
const path = require('path');

// Configuration
const TODO_FILE = path.join(__dirname, '../TODO.md');
const CHANGELOG_FILE = path.join(__dirname, '../CHANGELOG.md');
const VERSION = '1.0.12';

// ANSI colors
const colors = {
	reset: '\x1b[0m',
	green: '\x1b[32m',
	yellow: '\x1b[33m',
	red: '\x1b[31m',
	blue: '\x1b[34m',
	cyan: '\x1b[36m',
};

/**
 * Parse TODO.md and extract completed items for current version
 */
function parseCompletedTodos() {
	const todoContent = fs.readFileSync(TODO_FILE, 'utf8');
	const lines = todoContent.split('\n');

	let inCompletedSection = false;
	let inVersionSection = false;
	const completed = {
		added: [],
		changed: [],
		removed: [],
		fixed: [],
		technical: [],
	};

	for (let i = 0; i < lines.length; i++) {
		const line = lines[i].trim();

		// Track sections
		if (line.startsWith('## Completed Items')) {
			inCompletedSection = true;
			continue;
		}

		if (inCompletedSection && line.startsWith('###')) {
			inVersionSection = false;
		}

		if (inCompletedSection && line.includes('v' + VERSION)) {
			inVersionSection = true;
		}

		// Extract completed items
		if (inCompletedSection && inVersionSection && line.startsWith('- [x]')) {
			const item = line.replace('- [x]', '').trim();

			// Categorize by keywords
			if (
				item.toLowerCase().includes('added') ||
				item.toLowerCase().includes('created') ||
				item.toLowerCase().includes('implemented')
			) {
				completed.added.push(item);
			} else if (item.toLowerCase().includes('removed') || item.toLowerCase().includes('deleted')) {
				completed.removed.push(item);
			} else if (item.toLowerCase().includes('fixed') || item.toLowerCase().includes('fix:')) {
				completed.fixed.push(item);
			} else if (
				item.toLowerCase().includes('updated') ||
				item.toLowerCase().includes('changed') ||
				item.toLowerCase().includes('enhanced')
			) {
				completed.changed.push(item);
			} else {
				completed.added.push(item);
			}
		}

		// Stop after completed section
		if (inCompletedSection && line.startsWith('## ') && !line.includes('Completed')) {
			break;
		}
	}

	return completed;
}

/**
 * Generate changelog entry from completed TODOs
 */
function generateChangelogEntry(completed) {
	const today = new Date().toISOString().split('T')[0];
	let entry = `## [${VERSION}] - ${today}\n\n`;

	if (completed.added.length > 0) {
		entry += '### Added\n';
		completed.added.forEach((item) => {
			entry += `- ${item}\n`;
		});
		entry += '\n';
	}

	if (completed.changed.length > 0) {
		entry += '### Changed\n';
		completed.changed.forEach((item) => {
			entry += `- ${item}\n`;
		});
		entry += '\n';
	}

	if (completed.removed.length > 0) {
		entry += '### Removed\n';
		completed.removed.forEach((item) => {
			entry += `- ${item}\n`;
		});
		entry += '\n';
	}

	if (completed.fixed.length > 0) {
		entry += '### Fixed\n';
		completed.fixed.forEach((item) => {
			entry += `- ${item}\n`;
		});
		entry += '\n';
	}

	return entry;
}

/**
 * Update CHANGELOG.md with new entry
 */
function updateChangelog(newEntry) {
	const changelogContent = fs.readFileSync(CHANGELOG_FILE, 'utf8');
	const lines = changelogContent.split('\n');

	// Find insertion point (after [Unreleased])
	let insertIndex = -1;
	for (let i = 0; i < lines.length; i++) {
		if (lines[i].startsWith('## [Unreleased]')) {
			// Skip to end of Unreleased section
			while (i < lines.length && !lines[i].startsWith('## [')) {
				i++;
			}
			insertIndex = i;
			break;
		}
	}

	if (insertIndex === -1) {
		console.log(`${colors.red}❌ Could not find insertion point in CHANGELOG${colors.reset}`);
		return false;
	}

	// Check if entry already exists
	const today = new Date().toISOString().split('T')[0];
	const existingEntry = `## [${VERSION}] - ${today}`;
	if (changelogContent.includes(existingEntry)) {
		console.log(`${colors.yellow}⚠️  Entry for ${today} already exists${colors.reset}`);
		return false;
	}

	// Insert new entry
	lines.splice(insertIndex, 0, newEntry);

	// Write back
	fs.writeFileSync(CHANGELOG_FILE, lines.join('\n'), 'utf8');
	return true;
}

/**
 * Main function
 */
function main() {
	const autoMode = process.argv.includes('--auto');

	console.log(`${colors.cyan}╔════════════════════════════════════╗${colors.reset}`);
	console.log(`${colors.cyan}║   CHANGELOG Auto-Update Tool      ║${colors.reset}`);
	console.log(`${colors.cyan}╚════════════════════════════════════╝${colors.reset}\n`);

	// Parse TODO.md
	console.log(`${colors.blue}📖 Reading TODO.md...${colors.reset}`);
	const completed = parseCompletedTodos();

	const totalItems =
		completed.added.length +
		completed.changed.length +
		completed.removed.length +
		completed.fixed.length;

	if (totalItems === 0) {
		console.log(`${colors.yellow}⚠️  No completed items found for v${VERSION}${colors.reset}`);
		return;
	}

	console.log(`${colors.green}✅ Found ${totalItems} completed items${colors.reset}`);
	console.log(`   ${colors.green}Added:${colors.reset} ${completed.added.length}`);
	console.log(`   ${colors.yellow}Changed:${colors.reset} ${completed.changed.length}`);
	console.log(`   ${colors.red}Removed:${colors.reset} ${completed.removed.length}`);
	console.log(`   ${colors.blue}Fixed:${colors.reset} ${completed.fixed.length}\n`);

	// Generate changelog entry
	const newEntry = generateChangelogEntry(completed);

	console.log(`${colors.cyan}Preview:${colors.reset}`);
	console.log('─'.repeat(50));
	console.log(newEntry);
	console.log('─'.repeat(50) + '\n');

	if (!autoMode) {
		// Manual confirmation would go here
		console.log(`${colors.yellow}Run with --auto to update automatically${colors.reset}`);
		return;
	}

	// Update CHANGELOG
	console.log(`${colors.blue}📝 Updating CHANGELOG.md...${colors.reset}`);
	if (updateChangelog(newEntry)) {
		console.log(`${colors.green}✅ CHANGELOG.md updated successfully!${colors.reset}\n`);
		console.log(`${colors.yellow}💡 Don't forget to:${colors.reset}`);
		console.log(`   1. Review the generated changelog`);
		console.log(`   2. Add detailed descriptions if needed`);
		console.log(`   3. Commit the changes`);
	}
}

// Run
try {
	main();
} catch (error) {
	console.error(`${colors.red}❌ Error: ${error.message}${colors.reset}`);
	process.exit(1);
}
