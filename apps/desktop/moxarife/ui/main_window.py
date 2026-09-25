from __future__ import annotations

from PySide6.QtCore import Signal
from PySide6.QtWidgets import (
    QHBoxLayout,
    QLabel,
    QLineEdit,
    QListWidget,
    QListWidgetItem,
    QMainWindow,
    QPushButton,
    QVBoxLayout,
    QWidget,
)

from moxarife.api import ApiClient, ApiError
from moxarife.api.client import Session
from moxarife.storage import TokenStore


class MainWindow(QMainWindow):
    logged_out = Signal()

    def __init__(self, client: ApiClient, session: Session, token_store: TokenStore) -> None:
        super().__init__()
        self.client = client
        self.session = session
        self.token_store = token_store
        self.setWindowTitle("Moxarife — Inventário")
        self.resize(900, 600)

        self.search = QLineEdit()
        self.search.setPlaceholderText("Buscar por código, nome ou descrição")
        self.refresh = QPushButton("Atualizar")
        self.logout = QPushButton("Sair")
        self.items = QListWidget()
        self.message = QLabel()

        controls = QHBoxLayout()
        controls.addWidget(self.search)
        controls.addWidget(self.refresh)
        controls.addWidget(self.logout)

        layout = QVBoxLayout()
        layout.addWidget(QLabel(f"<h2>Inventário</h2><p>Bem-vindo, {session.user.get('name', '')}.</p>"))
        layout.addLayout(controls)
        layout.addWidget(self.items)
        layout.addWidget(self.message)
        container = QWidget()
        container.setLayout(layout)
        self.setCentralWidget(container)

        self.refresh.clicked.connect(self.load_items)
        self.search.returnPressed.connect(self.load_items)
        self.logout.clicked.connect(self._logout)
        self.load_items()

    def load_items(self) -> None:
        self.refresh.setEnabled(False)
        self.message.setText("Carregando...")
        try:
            result = self.client.list_inventory(search=self.search.text().strip())
            self.items.clear()
            for item in result.get("items", []):
                quantity = float(item.get("quantity", 0))
                text = f"{item.get('code', '')} — {item.get('name', '')} | {item.get('unit', '')} | estoque: {quantity:g}"
                self.items.addItem(QListWidgetItem(text))
            self.message.setText(f"{result.get('total', 0)} item(ns) encontrado(s).")
        except ApiError as exc:
            self.message.setText(str(exc))
        finally:
            self.refresh.setEnabled(True)

    def _logout(self) -> None:
        self.client.logout()
        self.token_store.clear()
        self.logged_out.emit()
