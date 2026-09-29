# Upcoming Event Cards

A WordPress shortcode that displays a responsive grid of upcoming events from
[The Events Calendar](https://theeventscalendar.com/) plugin.

## What it does

- Queries `tribe_events` posts for events that haven't ended yet and start
  within the next year
- Excludes one or more event categories (e.g. internal-only events) via a
  shortcode attribute
- Formats event dates to handle single-day, multi-day, and all-day events
  correctly
- Outputs a card grid with featured image, category, title, formatted dates,
  excerpt, and a "Learn More" link

## Usage

[upcoming_event_cards]
[upcoming_event_cards limit="3" exclude="internal-only,members"]

## Attributes

| Attribute | Default          | Description                              |
|-----------|------------------|-------------------------------------------|
| `limit`   | `6`              | Number of events to display               |
| `exclude` | `internal-only`  | Comma-separated category slugs to hide     |

## Context

Originally written for a client project on WordPress with The Events
Calendar. Client-identifying details have been anonymized. Built as a
custom presentation layer on top of the plugin's data, since its default
views didn't support the card-grid layout or category-exclusion logic
this project needed.
