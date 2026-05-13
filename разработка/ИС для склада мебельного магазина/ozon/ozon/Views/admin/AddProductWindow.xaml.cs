using ozon.Models;
using System;
using System.Windows;
using System.Windows.Controls;

namespace ozon.Views
{
    public partial class AddProductWindow : Window
    {
        public OzonContext Context { get; set; }

        public AddProductWindow()
        {
            InitializeComponent();
        }

        private async void OnAddClick(object sender, RoutedEventArgs e)
        {
            try
            {
                // Валидация обязательных полей
                if (string.IsNullOrEmpty(txtName.Text))
                {
                    MessageBox.Show("Заполните название товара.");
                    return;
                }

                if (!int.TryParse(txtQuantity.Text, out int quantity) || quantity < 0)
                {
                    MessageBox.Show("Введите корректное количество.");
                    return;
                }

                if (!int.TryParse(txtPrice.Text, out int price) || price < 0)
                {
                    MessageBox.Show("Введите корректную цену.");
                    return;
                }

                // Парсим габариты (необязательные поля)
                int length = int.TryParse(txtLength.Text, out int l) ? l : 0;
                int width = int.TryParse(txtWidth.Text, out int w) ? w : 0;
                int height = int.TryParse(txtHeight.Text, out int h) ? h : 0;

                var product = new Product
                {
                    Name = txtName.Text,
                    Description = txtDescription.Text,
                    ImgUrl = $"img\\{txtImgUrl.Text}",
                    Quantity = quantity,
                    Price = price,
                    lehgth = length,
                    width = width,
                    height = height
                };

                await Dispatcher.InvokeAsync(async () =>
                {
                    Context.Products.Add(product);
                    await Context.SaveChangesAsync();

                    MessageBox.Show("Товар успешно добавлен!");
                    Close();
                });
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Ошибка при сохранении товара: {ex.Message}");
            }
        }
    }
}