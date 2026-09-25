from __future__ import annotations

from PySide6.QtCore import Signal
from PySide6.QtWidgets import QLabel, QLineEdit, QPushButton, QVBoxLayout, QWidget

from moxarife.api import ApiClient, ApiError
from moxarife.config import Settings
from moxarife.storage import TokenStore


class LoginWindow(QWidget):
    logged_in = Signal(object)

    def __init__(self, client: ApiClient, settings: Settings, token_store: TokenStore) -> None:
        super().__init__()
        self.client = client
        self.settings = settings
        self.token_store = token_store
        self.setWindowTitle("Moxarife — Login")
        self.resize(420, 260)

        self.email = QLineEdit()
        self.email.setPlaceholderText("E-mail")
        self.password = QLineEdit()
        self.password.setPlaceholderText("Senha")
        self.password.setEchoMode(QLineEdit.Password)
        self.submit = QPushButton("Entrar")
        self.message = QLabel()
        self.message.setWordWrap(True)

        layout = QVBoxLayout(self)
        layout.addWidget(QLabel("<h2>Moxarife</h2><p>Faça login para continuar.</p>"))
        layout.addWidget(self.email)
        layout.addWidget(self.password)
        layout.addWidget(self.submit)
        layout.addWidget(self.message)

        self.submit.clicked.connect(self._login)
        self.password.returnPressed.connect(self._login)

    def _login(self) -> None:
        email = self.email.text().strip()
        password = self.password.text()
        if not email or not password:
            self.message.setText("Informe e-mail e senha.")
            return

        self.submit.setEnabled(False)
        self.message.setText("Autenticando...")
        try:
            session = self.client.login(email, password)
            self.token_store.save(session)
            self.logged_in.emit(session)
        except ApiError as exc:
            self.message.setText(str(exc))
        finally:
            self.submit.setEnabled(True)
