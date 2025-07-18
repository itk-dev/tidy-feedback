import { json } from '@sveltejs/kit';

export async function POST(event) {
	const { subject } = await event.request.json();

  // The default response status code (https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/201)
  let status = 201

  // Override status if requested
  const match = /respond with ([1-5][0-9]{2})/.exec(subject)
  if (match) {
    status = parseInt(match[1], 10)
  }

  return json({subject, status}, {status})
}
