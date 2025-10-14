# Loki AI - AI for Contao

Create custom prompts directly from DCA fields.
Combine multiple fields for fully dynamic content generation.
Trigger prompts via backend, console command, or manually.

Open source and free. 🚀

## Installation

### Install using Contao Manager

Search for **ai**, **chatgpt**, or **openai** and you will find this extension.

### Install using Composer

```bash
composer require plenta/loki-ai-bundle
```

### Configuration

You can find your Secret API key on the [API key page](https://platform.openai.com/api-keys).

Add the following parameter, including your API key, to your .env or .env.local

```bash
OPENAI_API_KEY=##YOUR-API-KEY##
```

## System requirements

- PHP: `^8.3`
- Contao: `^4.13` || `^5.3`

## Console command

You can run the prompts directly on the console.

```bash
php vendor/bin/contao-console loki:prompts:run
```
