from __future__ import annotations

import os
from dataclasses import dataclass


@dataclass(frozen=True)
class Settings:
    api_url: str
    request_timeout: float = 15.0

    @classmethod
    def from_environment(cls) -> "Settings":
        return cls(
            api_url=os.getenv("MOXARIFE_API_URL", "http://127.0.0.1:8080/api/v1").rstrip("/"),
            request_timeout=float(os.getenv("MOXARIFE_REQUEST_TIMEOUT", "15")),
        )
