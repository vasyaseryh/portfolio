using ozon.Models;
using System;
using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Linq;
using System.Runtime.CompilerServices;
using System.Windows;
using System.Windows.Media;

namespace ozon.Views
{
    public partial class ChangeStatusWindow : Window, INotifyPropertyChanged
    {
        public int Id;
        private string _selectedStatus;
        private string _statusColor = "#6C757D";
        private string _location;
        private bool _isLocationRequired = false;

        public ObservableCollection<string> Statuses { get; set; }

        public string SelectedStatus
        {
            get => _selectedStatus;
            set
            {
                _selectedStatus = value;
                OnPropertyChanged();
                StatusColor = GetStatusColor(value);
                OnPropertyChanged(nameof(StatusColor));
                UpdateLocationVisibility();
            }
        }

        public string StatusColor
        {
            get => _statusColor;
            set
            {
                _statusColor = value;
                OnPropertyChanged();
            }
        }

        public string Location
        {
            get => _location;
            set
            {
                _location = value;
                OnPropertyChanged();
            }
        }

        public bool IsLocationRequired
        {
            get => _isLocationRequired;
            set
            {
                _isLocationRequired = value;
                OnPropertyChanged();
            }
        }

        public ChangeStatusWindow(int id)
        {
            Id = id;

            Statuses = new ObservableCollection<string>()
            {
                "В пути на склад",
                "На складе",
                "Ожидает отгрузки",
                "В пути к клиенту",
                "Отменен"
            };

            InitializeComponent();

            // Устанавливаем высокое качество рендеринга
            SetTextQualityOptions();

            DataContext = this;
            LoadCurrentStatus();
        }

        private void SetTextQualityOptions()
        {
            TextOptions.SetTextFormattingMode(this, TextFormattingMode.Display);
            TextOptions.SetTextRenderingMode(this, TextRenderingMode.ClearType);
        }

        private void LoadCurrentStatus()
        {
            try
            {
                using (var context = new OzonContext())
                {
                    var order = context.Orders.FirstOrDefault(c => c.Id == Id);
                    if (order != null)
                    {
                        if (!string.IsNullOrEmpty(order.Status))
                        {
                            SelectedStatus = order.Status;

                            // Устанавливаем выбранный статус в комбобокс
                            foreach (var status in Statuses)
                            {
                                if (status == order.Status)
                                {
                                    comboBox.SelectedItem = status;
                                    break;
                                }
                            }
                        }
                        else
                        {
                            // Если статус не установлен, выбираем первый по умолчанию
                            SelectedStatus = Statuses[0];
                            comboBox.SelectedIndex = 0;
                        }

                        // Загружаем текущее расположение
                        Location = order.location;
                    }
                    else
                    {
                        // Если статус не установлен, выбираем первый по умолчанию
                        SelectedStatus = Statuses[0];
                        comboBox.SelectedIndex = 0;
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка загрузки текущего статуса: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
                SelectedStatus = Statuses[0];
                comboBox.SelectedIndex = 0;
            }
        }

        private void UpdateLocationVisibility()
        {
            if (SelectedStatus == "На складе" || SelectedStatus == "Ожидает отгрузки")
            {
                LocationPanel.Visibility = Visibility.Visible;
                IsLocationRequired = true;
            }
            else
            {
                LocationPanel.Visibility = Visibility.Collapsed;
                IsLocationRequired = false;
                Location = string.Empty; // Очищаем расположение при смене статуса
            }
        }

        private string GetStatusColor(string status)
        {
            if (string.IsNullOrEmpty(status)) return "#6C757D";

            switch (status.ToLower())
            {
                case "в пути на склад":
                case "в пути к клиенту":
                    return "#10B981";      // Яркий зеленый
                case "на складе":
                    return "#3B82F6";     // Яркий синий
                case "ожидает отгрузки":
                    return "#6B7280";     // Нейтральный серый
                case "отменен":
                    return "#EF4444";     // Яркий красный
                default:
                    return "#6B7280";     // Серый по умолчанию
            }
        }

        private void Apply_Click(object sender, RoutedEventArgs e)
        {
            try
            {
                if (string.IsNullOrEmpty(SelectedStatus))
                {
                    MessageBox.Show("Выберите статус заказа", "Внимание",
                        MessageBoxButton.OK, MessageBoxImage.Warning);
                    return;
                }

                // Проверяем обязательность заполнения расположения
                if (IsLocationRequired && string.IsNullOrWhiteSpace(Location))
                {
                    MessageBox.Show("Для выбранного статуса необходимо указать расположение на складе", "Внимание",
                        MessageBoxButton.OK, MessageBoxImage.Warning);
                    LocationTextBox.Focus();
                    return;
                }

                using (var context = new OzonContext())
                {
                    var order = context.Orders.FirstOrDefault(c => c.Id == Id);

                    if (order != null)
                    {
                        order.Status = SelectedStatus;

                        // Обновляем расположение только для соответствующих статусов
                        if (SelectedStatus == "На складе" || SelectedStatus == "Ожидает отгрузки")
                        {
                            order.location = Location?.Trim();
                        }
                        else
                        {
                            order.location = null; // Очищаем расположение для других статусов
                        }

                        // Обновляем даты в зависимости от статуса
                        UpdateOrderDates(order);

                        context.SaveChanges();

                        MessageBox.Show($"✅ Статус заказа успешно изменен на: {SelectedStatus}" +
                                      (IsLocationRequired ? $"\n📍 Расположение: {Location}" : ""),
                            "Успех", MessageBoxButton.OK, MessageBoxImage.Information);

                        DialogResult = true;
                        Close();
                    }
                    else
                    {
                        MessageBox.Show("❌ Заказ не найден", "Ошибка",
                            MessageBoxButton.OK, MessageBoxImage.Error);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"❌ Ошибка сохранения изменений: {ex.Message}",
                    "Ошибка", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        private void UpdateOrderDates(Order order)
        {
            var now = DateTime.Now;

            switch (SelectedStatus)
            {
                case "На складе":
                    if (!order.DeliveryTime.HasValue)
                        order.DeliveryTime = now;
                    break;
                case "Ожидает отгрузки":
                    if (!order.DeliveryTime.HasValue)
                        order.DeliveryTime = now;
                    break;
                case "В пути к клиенту":
                    if (!order.ShipmentTime.HasValue)
                        order.ShipmentTime = now;
                    break;
            }
        }

        private void Cancel_Click(object sender, RoutedEventArgs e)
        {
            DialogResult = false;
            Close();
        }

        private void ComboBox_SelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
        {
            // Дополнительная логика при изменении выбора в комбобоксе
        }

        public event PropertyChangedEventHandler PropertyChanged;
        protected virtual void OnPropertyChanged([CallerMemberName] string propertyName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }
    }
}