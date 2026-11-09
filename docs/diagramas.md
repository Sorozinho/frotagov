# Diagramas do FrotaGov

> O GitHub desenha estes diagramas automaticamente (Mermaid).
> Para colocar no artigo/slides: abra https://mermaid.live, cole o código e exporte como PNG.

## Diagrama de classes (UML)

```mermaid
classDiagram
    direction LR

    class Repositorio {
        <<interface>>
        +salvar(object entidade) void
        +buscarPorId(int id) object
        +listarTodos() array
        +excluir(int id) void
    }

    class RepositorioPdo {
        <<abstract>>
        #PDO pdo
        #tabela()* string
        #classeEntidade()* string
        +excluir(int id) void
        #verificarTipo(object e) void
    }

    class Auditavel {
        <<trait>>
        -DateTimeImmutable criadoEm
        -DateTimeImmutable atualizadoEm
        +marcarCriacao() void
        +marcarAtualizacao() void
    }

    class Secretaria {
        -int id
        -string nome
        -string sigla
        -Veiculo[] veiculos
        +adicionarVeiculo(Veiculo v) void
        +quantidadeVeiculos() int
    }

    class Veiculo {
        <<abstract>>
        -int id
        -int secretariaId
        -string descricao
        -float medidorAtual
        +getTipo()* string
        +getUnidadeMedidor()* string
        +intervaloRevisao()* float
        #calcularConsumo(float uso, float litros)* float
        +consumoMedio(Abastecimento[] lista) float
        +proximaRevisao() float
        +revisaoProxima() bool
        +registrarLeitura(float medidor) void
    }

    class Automovel {
        -string placa
        +intervaloRevisao() float
        #calcularConsumo(uso, litros) float
    }

    class MaquinaPesada {
        +intervaloRevisao() float
        #calcularConsumo(uso, litros) float
    }

    class Abastecimento {
        -int id
        -Veiculo veiculo
        -string data
        -float litros
        -float valorTotal
        -float medidor
        -string posto
        +precoPorLitro() float
    }

    class OrdemManutencao {
        -int id
        -Veiculo veiculo
        -string data
        -string oficina
        -string descricao
        -ItemServico[] itens
        +adicionarItem(tipo, descricao, qtd, valor) ItemServico
        +valorTotal() float
        +validarParaSalvar() void
    }

    class ItemServico {
        -string tipo
        -string descricao
        -float quantidade
        -float valorUnitario
        +subtotal() float
    }

    Veiculo <|-- Automovel : herança
    Veiculo <|-- MaquinaPesada : herança
    Secretaria o-- Veiculo : agregação
    Abastecimento --> Veiculo : associação
    OrdemManutencao --> Veiculo : associação
    OrdemManutencao *-- ItemServico : composição
    Repositorio <|.. RepositorioPdo : implementa
    RepositorioPdo <|-- SecretariaRepositorio
    RepositorioPdo <|-- VeiculoRepositorio
    RepositorioPdo <|-- AbastecimentoRepositorio
    RepositorioPdo <|-- OrdemManutencaoRepositorio
```

Classes que usam o trait `Auditavel`: Secretaria, Veiculo, Abastecimento, OrdemManutencao.

## Modelo do banco de dados (ER)

```mermaid
erDiagram
    secretaria ||--o{ veiculo : possui
    veiculo ||--o{ abastecimento : recebe
    veiculo ||--o{ ordem_manutencao : recebe
    ordem_manutencao ||--|{ item_servico : contem

    secretaria {
        int id PK
        varchar nome
        varchar sigla UK
        datetime criado_em
        datetime atualizado_em
    }
    veiculo {
        int id PK
        int secretaria_id FK
        enum tipo "automovel | maquina"
        varchar placa UK "nulo para máquina"
        varchar descricao
        decimal medidor_atual "km ou horas"
        datetime criado_em
        datetime atualizado_em
    }
    abastecimento {
        int id PK
        int veiculo_id FK
        date data
        decimal litros
        decimal valor_total
        decimal medidor
        varchar posto
        datetime criado_em
        datetime atualizado_em
    }
    ordem_manutencao {
        int id PK
        int veiculo_id FK
        date data
        varchar oficina
        varchar descricao
        datetime criado_em
        datetime atualizado_em
    }
    item_servico {
        int id PK
        int ordem_id FK "ON DELETE CASCADE"
        enum tipo "peca | servico"
        varchar descricao
        decimal quantidade
        decimal valor_unitario
    }
```
